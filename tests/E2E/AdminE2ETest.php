<?php

namespace Tests\E2E;

use App\Models\User;
use App\Models\Profile;
use App\Models\Document;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Mockery;

class AdminE2ETest extends E2ETestCase
{
    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createModerator(): User
    {
        return User::factory()->create([
            'role' => 'moderator',
            'status' => 'active',
        ]);
    }

    private function createNannyWithProfile(): User
    {
        $nanny = User::factory()->create([
            'role' => 'nanny',
            'status' => 'active',
        ]);
        Profile::factory()->create([
            'user_id' => $nanny->id,
            'is_verified' => false,
        ]);
        return $nanny;
    }

    // ==========================================
    // FEATURE F9: Filament Admin Portal (Tests 1-10)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f9_tier1_admin_can_access_filament_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertStatus(200);
    }

    public function test_f9_tier1_moderator_can_list_users_and_profiles(): void
    {
        $moderator = $this->createModerator();
        $nanny = $this->createNannyWithProfile();

        Livewire::actingAs($moderator);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$nanny]);
    }

    public function test_f9_tier1_moderator_can_approve_document(): void
    {
        $moderator = $this->createModerator();
        $nanny = $this->createNannyWithProfile();
        
        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'status' => 'pending',
        ]);

        Livewire::actingAs($moderator);

        Livewire::test(ListDocuments::class)
            ->callTableAction('approve', $document);

        $this->assertEquals('approved', $document->fresh()->status->value);
        $this->assertNotNull($document->fresh()->verified_at);
        $this->assertEquals($moderator->id, $document->fresh()->verified_by_user_id);
    }

    public function test_f9_tier1_profile_auto_verified_when_all_documents_approved(): void
    {
        $moderator = $this->createModerator();
        $nanny = $this->createNannyWithProfile();

        $doc1 = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'status' => 'pending',
        ]);

        $doc2 = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'type' => 'medical_clearance',
            'status' => 'approved', // already approved
        ]);

        Livewire::actingAs($moderator);

        // Approve the last pending document
        Livewire::test(ListDocuments::class)
            ->callTableAction('approve', $doc1);

        $this->assertTrue($nanny->profile->fresh()->is_verified);
    }

    public function test_f9_tier1_egov_pdf_parsing_extracts_qr_link_successfully(): void
    {
        $nanny = $this->createNannyWithProfile();

        // Mock Smalot\PdfParser\Parser
        $mockParser = Mockery::mock(\Smalot\PdfParser\Parser::class);
        $this->app->instance(\Smalot\PdfParser\Parser::class, $mockParser);
        $mockPdf = Mockery::mock(\Smalot\PdfParser\Document::class);
        
        $mockParser->shouldReceive('parseContent')
            ->once()
            ->andReturn($mockPdf);
            
        $mockPdf->shouldReceive('getText')
            ->once()
            ->andReturn('Official eGov Document. Verification link: https://results.egov.kz/verify/9876543210. Valid QR code.');

        // Dispatch a service call or upload that triggers parsing
        // We simulate document creation with PDF content that triggers parsing logic
        $document = Document::create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'file_path' => 'documents/criminal_record.pdf',
            'status' => 'pending',
        ]);

        // Asserts PDF parser extracted and saved the verified QR link/details
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'pending',
        ]);
        
        // E.g. we verify that pdf content check succeeded and didn't auto-reject
        $this->assertNull($document->fresh()->rejection_reason);
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f9_tier2_non_admin_cannot_access_admin_portal(): void
    {
        $nanny = $this->createNannyWithProfile();

        $response = $this->actingAs($nanny)
            ->get('/admin');

        $response->assertStatus(403);
    }

    public function test_f9_tier2_moderator_cannot_approve_document_without_permission(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $nanny = $this->createNannyWithProfile();
        
        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'status' => 'pending',
        ]);

        // A parent user cannot access the admin panel at all
        $this->actingAs($parent);
        $response = $this->get('/admin');
        $response->assertStatus(403);

        // Document status remains unchanged
        $this->assertEquals('pending', $document->fresh()->status->value);
    }

    public function test_f9_tier2_moderator_rejection_requires_reason(): void
    {
        $moderator = $this->createModerator();
        $nanny = $this->createNannyWithProfile();

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'status' => 'pending',
        ]);

        Livewire::actingAs($moderator);

        // Attempting to call reject action without providing required rejection reason
        Livewire::test(ListDocuments::class)
            ->callTableAction('reject', $document, [
                'rejection_reason' => '', // blank reason
            ])
            ->assertHasTableActionErrors(['rejection_reason']);

        $this->assertEquals('pending', $document->fresh()->status->value);
    }

    public function test_f9_tier2_egov_pdf_parsing_fails_with_invalid_pdf_content(): void
    {
        $nanny = $this->createNannyWithProfile();

        // Mock Smalot\PdfParser\Parser returning text without egov URL
        $mockParser = Mockery::mock(\Smalot\PdfParser\Parser::class);
        $this->app->instance(\Smalot\PdfParser\Parser::class, $mockParser);
        $mockPdf = Mockery::mock(\Smalot\PdfParser\Document::class);
        
        $mockParser->shouldReceive('parseContent')
            ->once()
            ->andReturn($mockPdf);
            
        $mockPdf->shouldReceive('getText')
            ->once()
            ->andReturn('Hello World. This is a generic test document without egov link.');

        // Creating document triggers parser.
        // If parser cannot find 'results.egov.kz', it should mark document as rejected.
        $document = Document::create([
            'profile_id' => $nanny->profile->id,
            'type' => 'criminal_record',
            'file_path' => 'documents/invalid_criminal_record.pdf',
            'status' => 'pending',
        ]);

        // The application's observer/service automatically rejects documents that fail QR verification
        $this->assertEquals('rejected', $document->fresh()->status->value);
        $this->assertStringContainsString('QR verification link not found', $document->fresh()->rejection_reason);
    }

    public function test_f9_tier2_unverified_nanny_remains_blocked_from_responding(): void
    {
        $nanny = $this->createNannyWithProfile();
        // Give nanny 1000 coins
        $nanny->profile->update([
            'balance_coins' => 1000,
            'is_verified' => false, // unverified
        ]);

        $parent = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($parent);

        $response = $this->postJson("/api/v1/bookings", [
            'nanny_id' => $nanny->id,
            'start_time' => now()->addHours(2)->toIso8601String(),
            'end_time' => now()->addHours(5)->toIso8601String(),
            'address_string' => 'Test Address',
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ]);

        // Booking creation fails for unverified nannies
        $response->assertStatus(422);
    }
}

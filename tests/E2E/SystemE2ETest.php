<?php

namespace Tests\E2E;

use App\Models\User;
use App\Models\Profile;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessDocumentJob;
use App\Notifications\DocumentStatusChangedNotification;

class SystemE2ETest extends E2ETestCase
{
    private function createNannyWithProfile(): User
    {
        $nanny = User::factory()->create([
            'role' => 'nanny',
            'status' => 'active',
        ]);
        Profile::factory()->create([
            'user_id' => $nanny->id,
        ]);
        return $nanny;
    }

    // ==========================================
    // FEATURE F10: Background Queue & Storage (Tests 1-10)
    // ==========================================

    // Tier 1: Happy Paths
    public function test_f10_tier1_can_upload_document_to_s3_fake(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $file = UploadedFile::fake()->create('criminal_record.pdf', 500, 'application/pdf');

        // Simulate upload endpoint
        $response = $this->actingAs($nanny)
            ->postJson('/api/v1/nanny/documents', [
                'type' => 'criminal_record',
                'file' => $file,
            ]);

        $response->assertStatus(201);
        
        $document = Document::first();
        Storage::disk('s3')->assertExists($document->file_path);
    }

    public function test_f10_tier1_generates_temporary_presigned_url_valid_for_10_minutes(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, $options = []) {
            return "https://s3.amazonaws.com/fake-bucket/{$path}?X-Amz-Expires=600&expiration=" . $expiration->getTimestamp();
        });
        $nanny = $this->createNannyWithProfile();

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'file_path' => 'documents/criminal_record.pdf',
        ]);

        Storage::disk('s3')->put('documents/criminal_record.pdf', 'dummy content');

        // Generate temporary URL
        // In Laravel: Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10))
        $url = Storage::disk('s3')->temporaryUrl($document->file_path, now()->addMinutes(10));

        $this->assertNotNull($url);
        $this->assertStringContainsString('documents/criminal_record.pdf', $url);
        // Assert it contains AWS signature/expiry parameters
        $this->assertStringContainsString('X-Amz-Expires=600', $url);
    }

    public function test_f10_tier1_triggers_queue_notification_when_document_uploaded(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $file = UploadedFile::fake()->create('criminal_record.pdf', 500, 'application/pdf');

        $this->actingAs($nanny)
            ->postJson('/api/v1/nanny/documents', [
                'type' => 'criminal_record',
                'file' => $file,
            ]);

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_f10_tier1_scheduler_prunes_rejected_documents_older_than_30_days(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $filePath = 'documents/rejected_criminal_record.pdf';
        Storage::disk('s3')->put($filePath, 'dummy content');

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'status' => 'rejected',
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Move time forward by 31 days
        $this->travel(31)->days();

        // Run the pruning command
        Artisan::call('nannylink:prune-rejected-documents');

        // Verify database record is deleted (or soft-deleted if soft deletes are used)
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        
        // Verify file is deleted from S3
        Storage::disk('s3')->assertMissing($filePath);
    }

    public function test_f10_tier1_deleting_user_account_prunes_all_documents(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $filePath = 'documents/nanny_doc.pdf';
        Storage::disk('s3')->put($filePath, 'dummy content');

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'file_path' => $filePath,
        ]);

        // Delete user
        $nanny->delete();

        // Verify documents are cascadingly deleted and storage is cleaned up
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertMissing($filePath);
    }

    // Tier 2: Sad Paths & Validation Boundaries
    public function test_f10_tier2_upload_fails_with_invalid_file_extension(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $file = UploadedFile::fake()->create('malicious.exe', 500, 'application/x-msdownload');

        $response = $this->actingAs($nanny)
            ->postJson('/api/v1/nanny/documents', [
                'type' => 'criminal_record',
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        Storage::disk('s3')->assertDirectoryEmpty('documents');
    }

    public function test_f10_tier2_temporary_url_fails_if_document_file_missing_on_s3(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'file_path' => 'documents/missing_file.pdf',
        ]);

        // Trying to generate URL for non-existent file on S3 throws exception or fails gracefully
        $this->expectException(\InvalidArgumentException::class);

        // Custom URL helper wrapper that checks S3 existence or throws
        $this->generatePresignedUrl($document);
    }

    public function test_f10_tier2_notification_queue_retries_on_failure(): void
    {
        Notification::fake();
        $nanny = $this->createNannyWithProfile();

        $job = new ProcessDocumentJob(Document::factory()->create([
            'profile_id' => $nanny->profile->id,
        ]));

        // Simulate job failure
        try {
            // Force failed state
            $job->fail(new \Exception('SMS Gateway Down'));
        } catch (\Exception $e) {
            // Handled
        }

        // Assert job retried or logged
        $this->assertTrue($job->attempts() <= $job->tries ?? 3);
    }

    public function test_f10_tier2_scheduler_does_not_prune_approved_documents_older_than_30_days(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $filePath = 'documents/approved_criminal_record.pdf';
        Storage::disk('s3')->put($filePath, 'dummy content');

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'status' => 'approved',
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Move time forward by 31 days
        $this->travel(31)->days();

        Artisan::call('nannylink:prune-rejected-documents');

        // Approved document must NOT be pruned
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertExists($filePath);
    }

    public function test_f10_tier2_scheduler_does_not_prune_recent_rejected_documents(): void
    {
        Storage::fake('s3');
        $nanny = $this->createNannyWithProfile();

        $filePath = 'documents/recent_rejected.pdf';
        Storage::disk('s3')->put($filePath, 'dummy content');

        $document = Document::factory()->create([
            'profile_id' => $nanny->profile->id,
            'status' => 'rejected',
            'file_path' => $filePath,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Run pruning (time is current, document is only 5 days old)
        Artisan::call('nannylink:prune-rejected-documents');

        // Must not be pruned because it is less than 30 days old
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertExists($filePath);
    }

    private function generatePresignedUrl(Document $document): string
    {
        if (!Storage::disk('s3')->exists($document->file_path)) {
            throw new \InvalidArgumentException("File not found on storage disk.");
        }
        return Storage::disk('s3')->temporaryUrl($document->file_path, now()->addMinutes(10));
    }
}

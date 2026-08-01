<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Enums\UserRole;
use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Authorize: role check (must be nanny)
        if ($user->role !== UserRole::NANNY) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:criminal_record,medical_clearance,identity_card,narcology_clearance,psychiatry_clearance'],
            'file' => ['required', 'file', 'max:10240'], // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации файла.',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $user->profile;
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found.'
            ], 404);
        }

        try {
            // Upload file
            $diskName = config('filesystems.default', 'public');
            Storage::disk($diskName)->makeDirectory('documents');
            $path = $request->file('file')->store('documents', $diskName);

            $type = $request->input('type');

            // Check if document of this type already exists for this profile
            $existing = Document::where('profile_id', $profile->id)->where('type', $type)->first();

            if ($existing) {
                // Delete old file if present
                if ($existing->file_path) {
                    try {
                        Storage::disk($diskName)->delete($existing->file_path);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to delete old doc file: " . $e->getMessage());
                    }
                }
                $existing->update([
                    'file_path' => $path,
                    'status' => DocumentStatus::PENDING,
                    'rejection_reason' => null,
                    'verified_at' => null,
                    'verified_by_user_id' => null,
                ]);
                $document = $existing->fresh();
            } else {
                $document = Document::create([
                    'profile_id' => $profile->id,
                    'type' => $type,
                    'file_path' => $path,
                    'status' => DocumentStatus::PENDING,
                ]);
            }

            // Reset profile verification when a document is uploaded/re-uploaded
            $profile->update(['is_verified' => false]);

            // Dispatch background processing job safely
            try {
                ProcessDocumentJob::dispatch($document);
            } catch (\Throwable $e) {
                Log::warning("ProcessDocumentJob dispatch warning: " . $e->getMessage());
            }

            return response()->json($document, 201);
        } catch (\Throwable $e) {
            Log::error("Document store error: " . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка при сохранении документа: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== UserRole::NANNY) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        $profile = $user->profile;
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found.'
            ], 404);
        }

        $document = Document::where('profile_id', $profile->id)->where('id', $id)->first();
        if (!$document) {
            return response()->json([
                'message' => 'Document not found.'
            ], 404);
        }

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully.'
        ], 200);
    }
}

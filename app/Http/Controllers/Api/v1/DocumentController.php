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
            'type' => ['required', 'string', 'in:criminal_record,medical_clearance'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:2048'], // PDF files, max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $user->profile;
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found.'
            ], 404);
        }

        // Upload to S3 (in local testing, Storage::fake('s3') is used)
        $path = $request->file('file')->store('documents', 's3');

        $document = Document::create([
            'profile_id' => $profile->id,
            'type' => $request->input('type'),
            'file_path' => $path,
            'status' => DocumentStatus::PENDING,
        ]);

        // Dispatch background processing job
        ProcessDocumentJob::dispatch($document);

        return response()->json($document, 201);
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

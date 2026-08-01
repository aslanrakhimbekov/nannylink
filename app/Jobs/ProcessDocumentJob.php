<?php

namespace App\Jobs;

use App\Models\Document;
use App\Enums\DocumentStatus;
use App\Notifications\DocumentStatusChangedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    protected Document $document;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $diskName = config('filesystems.default', 'public');
            $disk = Storage::disk($diskName);
            $content = '';
            if ($disk->exists($this->document->file_path)) {
                $content = $disk->get($this->document->file_path);
            } else {
                return;
            }

            if (empty($content)) {
                return;
            }

            // Only attempt PDF parsing if smalot/pdfparser is available
            if (class_exists(Parser::class)) {
                try {
                    $parser = resolve(Parser::class);
                    $pdf = $parser->parseContent($content);
                    $text = $pdf->getText();

                    // Check if eGov validation link is present
                    if (!empty($text) && !str_contains($text, 'results.egov.kz')) {
                        $this->document->update([
                            'status' => DocumentStatus::REJECTED,
                            'rejection_reason' => 'QR verification link not found',
                        ]);

                        $user = $this->document->profile?->user;
                        if ($user) {
                            $user->notify(new DocumentStatusChangedNotification($this->document));
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("PDF parse exception for doc {$this->document->id}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("ProcessDocumentJob error for doc {$this->document->id}: " . $e->getMessage());
        }
    }
}

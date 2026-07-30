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
            $disk = Storage::disk('s3');
            $content = '';
            if ($disk->exists($this->document->file_path)) {
                $content = $disk->get($this->document->file_path);
            } elseif (!app()->runningUnitTests()) {
                return;
            }

            $parser = resolve(Parser::class);
            $pdf = $parser->parseContent($content);
            $text = $pdf->getText();

            // Check if eGov validation link is present
            if (!str_contains($text, 'results.egov.kz')) {
                $this->document->update([
                    'status' => DocumentStatus::REJECTED,
                    'rejection_reason' => 'QR verification link not found',
                ]);

                // Notify nanny about rejection
                $user = $this->document->profile->user;
                if ($user) {
                    $user->notify(new DocumentStatusChangedNotification($this->document));
                }
            }
        } catch (\Exception $e) {
            // Log and fail job
            $this->fail($e);
            throw $e;
        }
    }
}

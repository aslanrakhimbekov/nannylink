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

            // Optional background indexing or parsing can be done here.
            // Automatic rejection disabled to allow human admin moderation.
            return;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Enums\DocumentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneRejectedDocuments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nannylink:prune-rejected-documents';

    /**
     * The console command description.
     */
    protected $description = 'Prune rejected nanny documents older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(30);

        $documents = Document::where('status', DocumentStatus::REJECTED)
            ->where('updated_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($documents as $document) {
            $document->delete();
            $count++;
        }

        $this->info("Successfully pruned {$count} rejected documents older than 30 days.");
    }
}

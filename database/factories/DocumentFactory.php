<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Profile;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'type' => DocumentType::CRIMINAL_RECORD,
            'file_path' => 'documents/criminal_record.pdf',
            'status' => DocumentStatus::PENDING,
        ];
    }
}

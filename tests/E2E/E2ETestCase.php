<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

abstract class E2ETestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, $options = []) {
            return "https://s3.amazonaws.com/fake-bucket/{$path}?X-Amz-Expires=600&expiration=" . $expiration->getTimestamp();
        });

        // Mock Smalot\PdfParser\Parser globally for E2E tests
        $mockParser = \Mockery::mock(\Smalot\PdfParser\Parser::class);
        $this->app->instance(\Smalot\PdfParser\Parser::class, $mockParser);
        $mockPdf = \Mockery::mock(\Smalot\PdfParser\Document::class);
        $mockParser->shouldReceive('parseContent')->andReturn($mockPdf)->byDefault();
        $mockPdf->shouldReceive('getText')->andReturn('results.egov.kz verify QR valid document')->byDefault();

        Redis::select(15);
        Redis::flushdb();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImageOptimizationService;
use App\Services\MediaUploadService;
use Mockery;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_extracts_video_ids_from_common_youtube_urls(): void
    {
        $service = $this->makeService();
        $expectedId = 'dQw4w9WgXcQ';

        $urls = [
            "https://www.youtube.com/watch?v={$expectedId}",
            "https://www.youtube.com/watch?v={$expectedId}&ab_channel=RickAstley",
            "https://m.youtube.com/watch?v={$expectedId}&feature=share",
            "https://music.youtube.com/watch?v={$expectedId}",
            "https://youtu.be/{$expectedId}?si=ABCDEFGHIJK",
            "https://www.youtube.com/embed/{$expectedId}",
            "https://www.youtube.com/shorts/{$expectedId}?feature=share",
            "https://youtube.com/live/{$expectedId}?feature=share",
            "youtube.com/watch?v={$expectedId}",
        ];

        foreach ($urls as $url) {
            $this->assertSame(
                $expectedId,
                $service->extractYouTubeVideoId($url),
                "Failed extracting ID from {$url}"
            );
            $this->assertTrue($service->isValidYouTubeUrl($url));
        }
    }

    public function test_it_rejects_invalid_youtube_urls(): void
    {
        $service = $this->makeService();

        $urls = [
            '',
            'just some text',
            'https://www.example.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?feature=share',
            'https://youtu.be/',
        ];

        foreach ($urls as $url) {
            $this->assertNull($service->extractYouTubeVideoId($url), "Unexpected ID extracted for {$url}");
            $this->assertFalse($service->isValidYouTubeUrl($url));
        }
    }

    private function makeService(): MediaUploadService
    {
        return new MediaUploadService(Mockery::mock(ImageOptimizationService::class));
    }
}

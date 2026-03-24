<?php

namespace Tests\Unit\Modules\Publisher;

use App\Modules\Publisher\MediaValidator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaValidatorTest extends TestCase
{
    private MediaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MediaValidator;
    }

    public function test_valid_jpeg_image_passes(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $errors = $this->validator->validate($file, 'post', 'image');
        $this->assertEmpty($errors);
    }

    public function test_image_over_4mb_fails(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 5000, 'image/jpeg'); // 5MB
        $errors = $this->validator->validate($file, 'post', 'image');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('4MB', $errors[0]);
    }

    public function test_invalid_image_mime_fails(): void
    {
        $file = UploadedFile::fake()->create('photo.gif', 100, 'image/gif');
        $errors = $this->validator->validate($file, 'post', 'image');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('JPEG', $errors[0]);
    }

    public function test_valid_mp4_video_passes(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 10240, 'video/mp4'); // 10MB
        $errors = $this->validator->validate($file, 'post', 'video');
        $this->assertEmpty($errors);
    }

    public function test_video_over_1gb_fails(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 1048577, 'video/mp4'); // just over 1GB
        $errors = $this->validator->validate($file, 'post', 'video');
        $this->assertNotEmpty($errors);
    }

    public function test_invalid_video_mime_fails(): void
    {
        $file = UploadedFile::fake()->create('video.avi', 100, 'video/avi');
        $errors = $this->validator->validate($file, 'reel', 'video');
        $this->assertNotEmpty($errors);
    }

    public function test_allowed_mimes_returns_correct_types(): void
    {
        $this->assertEquals(['image/jpeg', 'image/png'], $this->validator->allowedMimes('image'));
        $this->assertEquals(['video/mp4', 'video/quicktime'], $this->validator->allowedMimes('video'));
        $this->assertEquals([], $this->validator->allowedMimes('none'));
    }
}

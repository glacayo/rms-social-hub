<?php

namespace App\Modules\Publisher;

use Illuminate\Http\UploadedFile;

class MediaValidator
{
    // Image constraints
    const MAX_IMAGE_SIZE_MB = 4;

    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png'];

    // Video constraints
    const MAX_VIDEO_SIZE_MB = 1024; // 1GB

    const MAX_VIDEO_DURATION_SECONDS = 90; // Reels max

    const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/quicktime']; // mp4, mov

    // Aspect ratio tolerance (percentage)
    const ASPECT_RATIO_TOLERANCE = 0.05;

    // Required aspect ratio for Reels and Stories (9:16 = 0.5625)
    const VERTICAL_ASPECT_RATIO = 9 / 16;

    /**
     * Validate media file for a given post type.
     * Returns array of validation errors (empty = valid).
     */
    public function validate(UploadedFile $file, string $postType, string $mediaType): array
    {
        $errors = [];

        if ($mediaType === 'image') {
            $errors = array_merge($errors, $this->validateImage($file));
        } elseif ($mediaType === 'video') {
            $errors = array_merge($errors, $this->validateVideo($file, $postType));
        }

        return $errors;
    }

    private function validateImage(UploadedFile $file): array
    {
        $errors = [];

        // Size check
        $sizeMB = $file->getSize() / (1024 * 1024);
        if ($sizeMB > self::MAX_IMAGE_SIZE_MB) {
            $errors[] = 'La imagen no puede superar '.self::MAX_IMAGE_SIZE_MB.'MB. Tamaño actual: '.round($sizeMB, 1).'MB.';
        }

        // MIME type check
        if (! in_array($file->getMimeType(), self::ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Formato de imagen no permitido. Solo se aceptan JPEG y PNG.';
        }

        return $errors;
    }

    private function validateVideo(UploadedFile $file, string $postType): array
    {
        $errors = [];

        // Size check
        $sizeMB = $file->getSize() / (1024 * 1024);
        if ($sizeMB > self::MAX_VIDEO_SIZE_MB) {
            $errors[] = 'El video no puede superar '.self::MAX_VIDEO_SIZE_MB.'MB.';
        }

        // MIME type check
        if (! in_array($file->getMimeType(), self::ALLOWED_VIDEO_TYPES)) {
            $errors[] = 'Formato de video no permitido. Solo se aceptan MP4 y MOV.';
        }

        // Aspect ratio check for Reels and Stories
        if (in_array($postType, ['reel', 'story'])) {
            $errors = array_merge($errors, $this->validateAspectRatio($file->getRealPath()));
        }

        return $errors;
    }

    private function validateAspectRatio(string $filePath): array
    {
        // Use ffprobe if available, fall back to getimagesize for images
        // For videos, we attempt to get dimensions via getimagesize (works for some formats)
        // In production, install ffprobe for reliable video dimension detection
        $errors = [];

        // Try to get video dimensions using PHP's built-in functions
        // This is a best-effort check; ffprobe is more reliable
        $info = @getimagesize($filePath);
        if ($info && isset($info[0], $info[1]) && $info[0] > 0 && $info[1] > 0) {
            $actualRatio = $info[0] / $info[1];
            $expectedRatio = self::VERTICAL_ASPECT_RATIO;
            $tolerance = $expectedRatio * self::ASPECT_RATIO_TOLERANCE;

            if (abs($actualRatio - $expectedRatio) > $tolerance) {
                $errors[] = 'Los Reels y Stories requieren proporción 9:16 (vertical). La proporción actual es '.
                    round($info[0]).'x'.round($info[1]).'.';
            }
        }
        // If we can't determine dimensions, we skip the check (not fail)
        // The Meta API will reject it if truly invalid

        return $errors;
    }

    /**
     * Get allowed MIME types for a media type (for frontend hints)
     */
    public function allowedMimes(string $mediaType): array
    {
        return match ($mediaType) {
            'image' => self::ALLOWED_IMAGE_TYPES,
            'video' => self::ALLOWED_VIDEO_TYPES,
            default => [],
        };
    }
}

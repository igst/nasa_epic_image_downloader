<?php

declare(strict_types=1);

namespace App\Api\Nasa;

use App\Api\Nasa\Domain\ImageMetadata;

interface EpicApiInterface
{
    /**
     * @return array<ImageMetadata>
     */
    public function getImagesMetadataByDate(\DateTimeInterface $dateTime): array;
    public function downloadImage(ImageMetadata $imageMetadata, string $targetFolder): string;
    public function getLastAvailableDate(): \DateTimeInterface;
}

<?php

declare(strict_types=1);

namespace App\Api\Nasa;

use App\Api\Nasa\Domain\ImageMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

final readonly class EpicApi implements EpicApiInterface
{
    public function __construct(
        private HttpClientInterface $nasaEpicApi,
        private LoggerInterface $logger,
        private string $nasaEpicImageStorageDirectory,
    ) {
    }

    /**
     * @return array<ImageMetadata>
     */
    public function getRecentImagesMetadata(): array
    {
        $this->logger->debug('Fetching recent images metadata');

        $response = $this->nasaEpicApi->request(
            Request::METHOD_GET,
            'api/natural/images',
        );

        $imagesMetadata = [];

        $response = $response->toArray(false);

        $this->logger->debug('Fetched recent images metadata.', $response);

        foreach ($response as $imageMetadataArray) {
            $imagesMetadata[] = ImageMetadata::fromResponse($imageMetadataArray);
        }

        return $imagesMetadata;
    }

    /**
     * @return array<ImageMetadata>
     */
    public function getImagesMetadataByDate(\DateTimeInterface $dateTime): array
    {
        $this->logger->debug(sprintf('Fetching images metadata by date: %s', $dateTime->format('Y-m-d')));

        $response = $this->nasaEpicApi->request(
            Request::METHOD_GET,
            sprintf('api/natural/date/%s', $dateTime->format('Y-m-d')),
        );

        $imagesMetadata = [];

        $response = $response->toArray(false);

        $this->logger->debug(sprintf('Fetched images metadata by date: %s', $dateTime->format('Y-m-d')), $response);

        foreach ($response as $imageMetadataArray) {
            $imagesMetadata[] = ImageMetadata::fromResponse($imageMetadataArray);
        }

        return $imagesMetadata;
    }

    public function downloadImage(ImageMetadata $imageMetadata, string $targetFolder): void
    {
        $targetFolder = $this->nasaEpicImageStorageDirectory.\DIRECTORY_SEPARATOR.$targetFolder;

        $this->logger->debug(sprintf('Downloading image %s to directory %s', $imageMetadata->getImage(), $targetFolder));

        $filename = $imageMetadata->getImage().'.png';

        $response = $this->nasaEpicApi->request(
            Request::METHOD_GET,
            sprintf(
                'archive/natural/%d/%02d/%02d/png/%s',
                $imageMetadata->getDate()->format('Y'),
                $imageMetadata->getDate()->format('m'),
                $imageMetadata->getDate()->format('d'),
                $filename,
            ),
        );

        $content = $response->getContent();

        Assert::minLength($content, 10000);

        $this->logger->debug(sprintf('Downloaded image %s with %d bytes to directory %s', $imageMetadata->getImage(), \strlen($content), $targetFolder));

        if (!file_exists($targetFolder)) {
            mkdir($targetFolder, 0o700);
        }

        $filePathName = $targetFolder.\DIRECTORY_SEPARATOR.$filename;

        file_put_contents($filePathName, $content);
    }
}

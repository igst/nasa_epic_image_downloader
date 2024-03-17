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

    public function getLastAvailableDate(): \DateTimeInterface
    {
        $this->logger->debug('Fetching last available date');

        $response = $this->nasaEpicApi->request(
            Request::METHOD_GET,
            'api/natural/all',
        );

        $response = $response->toArray();

        $dates = [];

        foreach ($response as $dateItem) {
            $dates[] = new \DateTimeImmutable($dateItem['date']);
        }

        $date = max($dates);

        $this->logger->debug('Fetched last available date', ['date' => $date]);

        return $date;
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

    public function downloadImage(ImageMetadata $imageMetadata, string $targetFolder): string
    {
        $filename = $imageMetadata->getImage().'.png';
        $day = sprintf('%02d', $imageMetadata->getDate()->format('d'));
        $month = sprintf('%02d', $imageMetadata->getDate()->format('m'));
        $year = $imageMetadata->getDate()->format('Y');

        Assert::range((int) $year, 1960, 2040);

        $targetFolder = sprintf('%s/%s/%d%d%d/', $this->nasaEpicImageStorageDirectory, $targetFolder, $year, $month, $day);

        $this->logger->debug(sprintf('Downloading image %s to directory %s', $imageMetadata->getImage(), $targetFolder));

        $response = $this->nasaEpicApi->request(
            Request::METHOD_GET,
            sprintf(
                'archive/natural/%d/%02d/%02d/png/%s',
                $year,
                $month,
                $day,
                $filename,
            ),
        );

        $content = $response->getContent();

        Assert::minLength($content, 10000);

        $this->logger->debug(sprintf('Downloaded image %s with %d bytes to directory %s', $imageMetadata->getImage(), \strlen($content), $targetFolder));

        if (!file_exists($targetFolder)) {
            mkdir($targetFolder, 0o700, true);
        }

        $filePathName = $targetFolder.\DIRECTORY_SEPARATOR.$filename;

        file_put_contents($filePathName, $content);

        return $filePathName;
    }
}

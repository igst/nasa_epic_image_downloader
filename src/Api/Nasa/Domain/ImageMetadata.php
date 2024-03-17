<?php

declare(strict_types=1);

namespace App\Api\Nasa\Domain;

use Webmozart\Assert\Assert;

final readonly class ImageMetadata
{
    private function __construct(
        private string $identifier,
        private string $caption,
        private string $image,
        private string $version,
        private \DateTimeImmutable $date,
    ) {
        Assert::notEmpty($identifier);
        Assert::notEmpty($caption);
        Assert::notEmpty($image);
        Assert::notEmpty($version);
        Assert::notEmpty($date);
    }

    public static function fromResponse(array $response)
    {
        Assert::keyExists($response, 'identifier');
        Assert::keyExists($response, 'caption');
        Assert::keyExists($response, 'image');
        Assert::keyExists($response, 'version');
        Assert::keyExists($response, 'date');

        Assert::notEmpty($response['identifier']);
        Assert::notEmpty($response['caption']);
        Assert::notEmpty($response['image']);
        Assert::notEmpty($response['version']);
        Assert::notEmpty($response['date']);

        Assert::regex($response['date'], '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/');

        return new self(
            $response['identifier'],
            $response['caption'],
            $response['image'],
            $response['version'],
            new \DateTimeImmutable($response['date']),
        );
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}

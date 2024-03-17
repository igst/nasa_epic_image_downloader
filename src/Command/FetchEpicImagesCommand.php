<?php

declare(strict_types=1);

namespace App\Command;

use App\Api\Nasa\EpicApiInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nasa:epic',
    description: 'Fetches the NASA EPIC Images',
)]
final class FetchEpicImagesCommand extends Command
{
    public function __construct(private readonly EpicApiInterface $epicApi)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addArgument('target-directory', InputArgument::REQUIRED, 'Specifies the target directory where the images should be stored, relative to the ENV variable NASA_EPIC_IMAGE_STORAGE_DIRECTORY.')
            ->addArgument('date', InputArgument::OPTIONAL, 'Specifies the date for which images should be downloaded. If no value is given, the last available date will be used');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $targetDirectory = $input->getArgument('target-directory');

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $targetDirectory)) {
            $io->error('Argument for target-directory contains illegal characters');

            return Command::FAILURE;
        }

        $date = $input->getArgument('date');

        if (null === $date) {
            $date = $this->epicApi->getLastAvailableDate();
            $io->warning(sprintf('No date given. Will use last available date: %s', $date->format('Y-m-d')));
        } else {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (\DateMalformedStringException $e) {
                $io->error('Cannot parse the date string from argument "date"');

                return Command::FAILURE;
            }
        }

        $imagesMetadata = $this->epicApi->getImagesMetadataByDate($date);

        $imagesAmount = \count($imagesMetadata);

        if (1 > $imagesAmount) {
            $io->warning(sprintf('No images found for date %s', $date->format('Y-m-d')));

            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d images for date %s. Starting download...', $imagesAmount, $date->format('Y-m-d')));

        $progressBar = new ProgressBar($output, $imagesAmount);

        $imageFilePaths = [];

        foreach ($imagesMetadata as $imageMetadata) {
            $imageFilePaths[] = $this->epicApi->downloadImage($imageMetadata, $targetDirectory);
            $progressBar->advance();
        }

        $progressBar->finish();

        $io->newLine(2);
        $io->writeln('Following files were downloaded:');
        $io->newLine(1);
        $io->listing($imageFilePaths);

        return Command::SUCCESS;
    }
}

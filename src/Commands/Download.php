<?php
namespace UAM\Commands;

use DOMDocument;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use UAM\Cloud\API as CloudAPI;
use UAM\Cloud\File;
use UAM\Exceptions\InvalidArgument;
use UAM\Exceptions\RequiredArgument;

class Download extends Command
{
    private $login;
    private $password;
    private $target;
    private $source;
    private $maxSize;
    private $rewrite;

    protected function configure()
    {
        $this->setName('download')
            ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'you@mail.ru')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, '$ecr3t')
            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Target directory')
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Root directory in the cloud')
            ->addOption(
                'max-size', 'm',
                InputOption::VALUE_OPTIONAL,
                'Skip files bigger than this amount of megabytes'
            )
            ->addOption(
                'rewrite', 'r',
                InputOption::VALUE_NONE,
                'Download files that already exists on a disk and have the same size anyway'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        if (!$input->getOption('login')) {
            $question = new Question("<question>What is your mail.ru email address?</question>\n");

            $input->setOption('login', $helper->ask($input, $output, $question));
        }

        if (!$input->getOption('password')) {
            $question = new Question("<question>What is your account password?</question>\n");

            $input->setOption('password', $helper->ask($input, $output, $question));
        }

        if (!$input->getOption('target')) {
            $question = new Question("<question>In which folder files should be downloaded?</question>\n");

            $input->setOption('target', $helper->ask($input, $output, $question));
        }

        if (!$input->getOption('source')) {
            $question = new Question("<question>Which folder in the cloud you want to download? (/)</question>\n", '/');

            $input->setOption('source', $helper->ask($input, $output, $question));
        }

        if (!$input->getOption('max-size')) {
            $question = new Question("<question>What is a file size limit in megabytes? (2048 Mb)</question>\n", 2048);

            $input->setOption('max-size', (int)$helper->ask($input, $output, $question));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setParams($input);

        $logger = new ConsoleLogger($output);

        $logger->notice('Started at {time}', ['time' => date('H:i:s d-m-Y')]);

        $logger->info('Requesting files from the cloud.');

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            new Client(['cookies' => true, 'debug' => $output->isDebug()]),
            new DOMDocument(),
            $logger
        );

        $files = $cloud->getFiles($this->source);

        $logger->notice(
            'Got info about {count} files from the cloud.',
            [
                'count' => count($files)
            ]
        );

        $logger->notice(
            'Total size is {size}.',
            [
                'size' => format_file_size($this->calculateTotalSize($files))
            ]
        );

        $files = $this->skipLargeFiles($files, $logger);

        $progress = $this->getProgressBar($output, count($files), $this->calculateTotalSize($files));

        $progress->start();

        $this->downloadFiles($files, $cloud, $progress);

        $progress->setMessage('<info>all done!</info>', 'file');
        $progress->clear();
        $progress->finish();

        $this->renderResultTable($output, $files);

        $logger->notice('Finished at {time}', ['time' => date('H:i:s d-m-Y')]);
    }

    private function setParams(InputInterface $input)
    {
        $this->login = trim($input->getOption('login'));
        $this->password = $input->getOption('password');
        $this->target = trim($input->getOption('target'));
        $this->source = trim($input->getOption('source'));
        $this->maxSize = (int)$input->getOption('max-size') * 1024 * 1024;
        $this->rewrite = (bool)$input->getOption('rewrite');

        $this->validateParams();
    }

    private function validateParams()
    {
        if (!$this->login) {
            throw new RequiredArgument('login');
        }

        if (!$this->password) {
            throw new RequiredArgument('password');
        }

        if (!$this->target) {
            throw new RequiredArgument('target');
        }

        if (!is_dir($this->target)) {
            throw new InvalidArgument('target', 'existing directory');
        }

        if (!is_writable($this->target)) {
            throw new InvalidArgument('target', 'writable');
        }

        if (!$this->maxSize) {
            throw new RequiredArgument('max-size');
        }

        if (!is_int($this->maxSize) || $this->maxSize <= 0) {
            throw new InvalidArgument('max-size', 'integer greater than zero');
        }
    }

    /**
     * @param $files
     * @return mixed
     */
    protected function calculateTotalSize($files)
    {
        return array_reduce($files, function ($carry, File $file) {
            return $carry + $file->getSize();
        }, 0);
    }

    /**
     * @param $files
     * @param $logger
     * @return array
     */
    protected function skipLargeFiles($files, ConsoleLogger $logger)
    {
        $limit = $this->maxSize;

        $files = array_filter($files, function (File $file) use ($logger, $limit) {
            if ($file->getSize() > $this->maxSize) {
                $logger->info(
                    'File {file} is skipped because it is bigger than {limit}.',
                    [
                        'file' => $file->getPath(),
                        'limit' => format_file_size($limit),
                    ]
                );

                return false;
            }

            return true;
        });

        return $files;
    }

    /**
     * @param OutputInterface $output
     * @param $count
     * @param $size
     * @return ProgressBar
     */
    protected function getProgressBar(OutputInterface $output, $count, $size)
    {
        $progress = new ProgressBar($output, $size);

        $progress->setPlaceholderFormatterDefinition(
            'current',
            function (ProgressBar $progress) {
                return format_file_size($progress->getProgress());
            }
        );

        $progress->setPlaceholderFormatterDefinition(
            'max',
            function (ProgressBar $progress) {
                return format_file_size($progress->getMaxSteps());
            }
        );

        $lines = [
            '%bar% <comment>%percent:3s%%</comment>',
        ];

        if ($output->isVerbose()) {
            $lines[] = '%current_index% / %total_count%, %current% / %max%';
        }

        if ($output->isVeryVerbose()) {
            $lines[] = 'Downloading: %file%';
        }

        $progress->setMessage('nothing', 'file');
        $progress->setMessage(0, 'current_index');
        $progress->setMessage($count, 'total_count');

        $progress->setEmptyBarCharacter('<fg=magenta;bg=default>*</>');
        $progress->setBarCharacter('<fg=yellow;bg=default>*</>');
        $progress->setProgressCharacter('<fg=green;bg=default>*</>');

        $progress->setBarWidth(100);

        for ($i = count($lines); $i > 1; $i--) {
            $output->writeln('');
        }

        $progress->setFormat(implode("\n", $lines));

        return $progress;
    }

    /**
     * @param File[] $files
     * @param CloudAPI $cloud
     * @param ProgressBar $progress
     */
    protected function downloadFiles(array $files, CloudAPI $cloud, ProgressBar $progress)
    {
        foreach (array_values($files) as $index => $file) {
            /** @var File $file */

            $progress->setMessage(
                sprintf(
                    '<info>%s</info> %s',
                    $file->getPath(),
                    format_file_size($file->getSize())
                ),
                'file'
            );
            $progress->setMessage($index + 1, 'current_index');

            $progress->clear();
            $progress->display();

            $cloud->download($file, $this->target, $this->rewrite);

            // A little delay, so progress won't update too fast when we are downloading a bunch of small files
            // Maybe it is not the best idea, but i'll keep it for now
            usleep(100000);

            $progress->clear();
            $progress->advance($file->getSize());
        }
    }

    /**
     * @param OutputInterface $output
     * @param $files
     */
    protected function renderResultTable(OutputInterface $output, $files)
    {
        $maxTableSize = 100;

        $files = array_map(function (File $file) {
            return [
                mb_substr($file->getPath(), 0, mb_strlen($file->getName()) * -1),
                $file->getName(),
                format_file_size($file->getSize()),
            ];
        }, $files);

        if (count($files) > $maxTableSize) {
            $tmp = array_slice($files, 0, $maxTableSize / 2);

            $tmp[] = [new TableCell(sprintf('... %d more ...', count($files) - $maxTableSize), ['colspan' => 3])];

            $files = array_merge($tmp, array_slice($files, $maxTableSize / 2 * -1));
        }

        $table = new Table($output);

        $table
            ->setHeaders(['Folder', 'Name', 'Size'])
            ->setRows($files);

        $output->writeln('');
        $output->writeln('');

        $table->render();
    }
}

<?php

namespace Elenyum\Maker\Command;

use Elenyum\Maker\Service\Module\ServiceMakeModule;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'elenyum:make',
    description: 'Created modules by esl specification file',
    aliases: ['e:m']
)]
class MakeCommand extends Command
{
    public function __construct(
        private readonly ServiceMakeModule $makeModule,
        private readonly LoggerInterface $logger,
        private readonly string $rootPath
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate code for modules, input format esl specification')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Input files for generation modules', null);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $file = file_get_contents($this->rootPath.'/'.$input->getOption('file'));
            $data = json_decode($file, true);
            $this->makeModule->createModule($data);
            $io->success('Modules generated.');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->logger->info($e->getMessage(), [$e->getCode(), $e->getFile(), $e->getLine()]);
            $io->error('Error generating modules. See logs for details.');

            return Command::FAILURE;
        }
    }
}
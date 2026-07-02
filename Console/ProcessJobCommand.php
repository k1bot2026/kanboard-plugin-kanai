<?php

namespace Kanboard\Plugin\KanAI\Console;

use Kanboard\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process one queued KanAI question job. Spawned detached by the web request so
 * the slow LLM call runs out-of-band and never blocks the browser / web server.
 *
 *   php cli kanai:process-job <job_id>
 */
class ProcessJobCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('kanai:process-job')
            ->setDescription('Process one queued KanAI question job (used internally)')
            ->addArgument('job_id', InputArgument::REQUIRED, 'Job id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = (int) $input->getArgument('job_id');
        $this->assistantService->runJob($jobId);
        $output->writeln('job ' . $jobId . ' processed');
        return 0;
    }
}

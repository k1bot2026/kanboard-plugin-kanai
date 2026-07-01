<?php

namespace Kanboard\Plugin\KanAI\Console;

use Kanboard\Console\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Autonomous KanAI digest: for every project that opted in, start a fresh
 * conversation and let the assistant produce a daily digest (status, cleanup
 * candidates, risks) with proposals that members review in the panel.
 *
 * Run from cron, e.g. daily:
 *   0 7 * * * php /path/to/kanboard/cli kanai:digest >/dev/null 2>&1
 */
class DigestCommand extends BaseCommand
{
    private const INSTRUCTION = 'Produce a short daily digest of this project: '
        . '1) status per column and notable recent changes, '
        . '2) tasks needing cleanup (done-but-not-closed, overdue, stale, untagged, unassigned), '
        . '3) risks and blockers. Keep it compact (use Markdown). '
        . 'Propose concrete maintenance actions where clearly helpful.';

    protected function configure()
    {
        $this
            ->setName('kanai:digest')
            ->setDescription('Run the KanAI auto-digest for every opted-in project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->projectModel->getAllByStatus(1);
        $ran = 0;

        foreach ($projects as $project) {
            $projectId = (int) $project['id'];

            if (! $this->settingsModel->getProjectEnabled($projectId)) {
                continue;
            }
            if ($this->projectMetadataModel->get($projectId, 'kanai_auto_digest', '0') !== '1') {
                continue;
            }

            $output->writeln(sprintf('KanAI digest: project #%d "%s"', $projectId, $project['name']));

            try {
                // user_id 0 = system: the panel renders these as "KanAI (automatic)".
                $conversationId = $this->conversationModel->createConversation(
                    $projectId,
                    0,
                    '🤖 ' . t('Auto digest') . ' ' . date('Y-m-d')
                );
                $this->assistantService->ask($projectId, 0, $conversationId, self::INSTRUCTION);
                $output->writeln('  done');
                $ran++;
            } catch (\Throwable $e) {
                $output->writeln('  ERROR: ' . $e->getMessage());
            }
        }

        $output->writeln(sprintf('%d digest(s) generated', $ran));
        return 0;
    }
}

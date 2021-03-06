<?php

namespace Martiis\GitlabCLI\Command;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeRequestOpenCommand extends AbstractProjectAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('merge-request:open')
            ->setDescription('Opens a merge request.')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Branch to merge from.'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Branch to merge to'
            )
            ->addArgument(
                'title',
                InputArgument::REQUIRED,
                'Merge request title'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FilesystemCache $cache */
        $cache = $this->container->get('cache');
        if (!$cache->contains($input->getArgument('project'))) {
            throw new \LogicException('Project namespace not found! Try to clear cache.');
        }

        $id = $cache->fetch($input->getArgument('project'));
        /** @var ClientInterface $client */
        $client = $this->container->get('guzzle');
        $response = $client->request(
            'POST',
            sprintf('projects/%s/merge_requests', $id),
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode(
                    [
                        'id' => $id,
                        'target_branch' => $input->getArgument('target'),
                        'source_branch' => $input->getArgument('source'),
                        'title' => $input->getArgument('title'),
                    ]
                )
            ]
        );

        $encoded = json_decode($response->getBody()->getContents(), true);

        $this
            ->getIO($input, $output)
            ->success(sprintf(
                "Successfully opened new #%s merge-request!\n\n"
                . "Link: %sonego/api/merge_requests/%s",
                $encoded['iid'],
                $this->container->getParameter('gitlab_host'),
                $encoded['iid']
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $io = $this->getIO($input, $output);
        $fields = [
            'source' => 'Source branch',
            'target' => 'Target branch',
            'title' => 'Title'
        ];
        foreach ($fields as $name => $qstn) {
            $input->getArgument($name) === null && $input->setArgument($name, $io->ask($qstn));
        }
    }
}

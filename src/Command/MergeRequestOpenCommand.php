<?php

namespace Martiis\GitlabCLI\Command;

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
            ->setDescription('Opens merge request.')
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Branch to merge from.'
            )
            ->addArgument(
                'head',
                InputArgument::REQUIRED,
                'Branch to merge to'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ClientInterface $client */
        $client = $this->getBag()->get('guzzle');
        $response = $client->request('GET', 'projects');

        $this
            ->getIO($input, $output)
            ->block(json_encode(
                json_decode($response->getBody()->getContents()),
                JSON_PRETTY_PRINT
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $io = $this->getIO($input, $output);
        foreach (['target' => 'Target branch', 'head' => 'Head branch'] as $name => $qstn) {
            $input->setArgument($name, $io->ask($qstn));
        }
    }
}
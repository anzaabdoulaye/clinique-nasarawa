<?php

namespace App\Command;

use App\Service\PharmacyService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'pharmacy:alerts', description: 'Check lots near expiration and emit alerts')]
class PharmacyAlertsCommand extends Command
{
    public function __construct(private PharmacyService $pharmacy, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check lots near expiration and emit alerts')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Seuil en jours', 30)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');

        $lots = $this->pharmacy->getLotsNearExpiration($days);
        if (count($lots) === 0) {
            $output->writeln(sprintf('Aucun lot proche de péremption dans %d jours.', $days));
            return Command::SUCCESS;
        }

        foreach ($lots as $lot) {
            $msg = sprintf('Lot %s (Medicament: %s) expire le %s — qte: %d',
                $lot->getNumeroLot() ?: $lot->getId(),
                method_exists($lot, 'getMedicament') ? $lot->getMedicament()->getNom() : 'N/A',
                $lot->getDatePeremption() ? $lot->getDatePeremption()->format('Y-m-d') : '—',
                $lot->getQuantite()
            );
            $this->logger->warning($msg);
            $output->writeln($msg);
        }

        // Note: pour envoyer des emails, injecter MailerInterface et l'utiliser ici.

        return Command::SUCCESS;
    }
}

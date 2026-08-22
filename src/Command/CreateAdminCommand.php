<?php

namespace App\Command;

use App\Entity\User;
use App\Service\InvitationMailer;
use App\Service\InvitationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un administrateur et lui envoie une invitation par email',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly InvitationMailer $invitationMailer,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if (!\is_string($email)) {
            $io->error('L\'email doit être une chaîne de caractères.');

            return Command::INVALID;
        }

        if (\count($this->validator->validate($email, new Email())) > 0) {
            $io->error(sprintf('"%s" n\'est pas une adresse email valide.', $email));

            return Command::INVALID;
        }

        try {
            $user = $this->invitationService->invite($email, [User::ROLE_ADMIN]);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $token = $user->getInvitationToken();

        if (null === $token) {
            $io->error('Le token d\'invitation n\'a pas pu être généré.');

            return Command::FAILURE;
        }

        $this->invitationMailer->sendAdminInvitation($user, $token);

        $io->success(sprintf('Administrateur créé : %s', $email));
        $io->note(sprintf(
            'Une invitation vient d\'être envoyée. Le lien expire dans %d jours.',
            InvitationService::TOKEN_LIFETIME_DAYS,
        ));

        return Command::SUCCESS;
    }
}

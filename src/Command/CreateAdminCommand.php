<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create a new administrator account.')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly AdminUserRepository $repository,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Création d\'un administrateur LUXE CAR');

        $email = $this->askEmail($io);
        if ($this->repository->findOneBy(['email' => $email]) !== null) {
            $io->error(sprintf('Un administrateur avec l\'e-mail %s existe déjà.', $email));
            return Command::FAILURE;
        }

        $displayName = $io->ask('Nom affiché', validator: static function (?string $v): string {
            if ($v === null || trim($v) === '') throw new \RuntimeException('Le nom est obligatoire.');
            return trim($v);
        });

        $password = $this->askPassword($io);

        $admin = new AdminUser();
        $admin->setEmail($email)
              ->setDisplayName($displayName)
              ->setPassword($this->hasher->hashPassword($admin, $password))
              ->setRoles(['ROLE_ADMIN']);

        $this->em->persist($admin);
        $this->em->flush();

        $io->success(sprintf('Administrateur %s créé avec succès.', $email));
        return Command::SUCCESS;
    }

    private function askEmail(SymfonyStyle $io): string
    {
        return $io->ask('Adresse e-mail', validator: function (?string $v): string {
            $errors = $this->validator->validate($v, [new NotBlank(), new Email()]);
            if (\count($errors) > 0) throw new \RuntimeException('E-mail invalide.');
            return (string) $v;
        });
    }

    private function askPassword(SymfonyStyle $io): string
    {
        $question = (new Question('Mot de passe (min. 10 caractères)'))
            ->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function (?string $v): string {
                $errors = $this->validator->validate($v, [new NotBlank(), new Length(min: 10)]);
                if (\count($errors) > 0) throw new \RuntimeException('Mot de passe trop court.');
                return (string) $v;
            });

        return $io->askQuestion($question);
    }
}

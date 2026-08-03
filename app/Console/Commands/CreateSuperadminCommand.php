<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;
use Illuminate\Console\Command;
use Throwable;

class CreateSuperadminCommand extends Command
{
    /**
     * Nom et signature de la commande Artisan.
     *
     * @var string
     */
    protected $signature = 'make:superadmin 
                            {--org= : Nom de l\'organisation tenant}
                            {--name= : Nom complet du Superadmin}
                            {--email= : Adresse e-mail du Superadmin}
                            {--password= : Mot de passe du Superadmin}';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Créer une organisation et un compte Superadmin avec le rôle OWNER';

    /**
     * Exécuter la commande Console.
     */
    public function handle(TenantRegistrationService $registrationService): int
    {
        $this->info('🚀 Création d\'un compte Superadmin (Rôle OWNER)...');

        $org = $this->option('org') ?: $this->ask('Nom de l\'organisation Tenant', 'MetricsDash SuperAdmin Org');
        $name = $this->option('name') ?: $this->ask('Nom complet du Superadmin', 'Super Admin');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail du Superadmin');
        
        while (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('L\'adresse e-mail saisie n\'est pas valide.');
            $email = $this->ask('Veuillez saisir une adresse e-mail valide');
        }

        $password = $this->option('password') ?: $this->secret('Mot de passe (8 caractères minimum)');

        while (empty($password) || strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');
            $password = $this->secret('Veuillez saisir un mot de passe valide');
        }

        try {
            $inputDto = new RegisterTenantInputDTO(
                organizationName: (string) $org,
                ownerName: (string) $name,
                ownerEmail: (string) $email,
                password: (string) $password
            );

            $outputDto = $registrationService->register($inputDto);

            $this->newLine();
            $this->info('✅ Compte Superadmin créé avec succès !');
            $this->table(
                ['Champ', 'Valeur'],
                [
                    ['Tenant ID', $outputDto->tenantId],
                    ['Organisation', $outputDto->organizationName],
                    ['Slug Organisation', $outputDto->slug],
                    ['ID Superadmin', $outputDto->ownerUserId],
                    ['Nom Superadmin', $outputDto->ownerName],
                    ['E-mail Superadmin', $outputDto->ownerEmail],
                    ['Rôle attribué', 'OWNER (Superadmin)'],
                ]
            );

            $this->newLine();
            $this->warn('🔑 Jeton d\'authentification Bearer généré :');
            $this->line($outputDto->token);
            $this->newLine();

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('❌ Échec de la création du Superadmin : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

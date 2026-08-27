<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature   = 'vapid:generate';
    protected $description = 'Generate VAPID keys for Web Push Notifications';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID keys generated successfully!');
        $this->newLine();
        $this->line('Add these to your <comment>.env</comment> file:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->newLine();

        if ($this->confirm('Write keys to .env automatically?', true)) {
            $envPath    = base_path('.env');
            $envContent = file_get_contents($envPath);

            $envContent = preg_replace('/VAPID_PUBLIC_KEY=.*/', 'VAPID_PUBLIC_KEY=' . $keys['publicKey'], $envContent);
            $envContent = preg_replace('/VAPID_PRIVATE_KEY=.*/', 'VAPID_PRIVATE_KEY=' . $keys['privateKey'], $envContent);

            file_put_contents($envPath, $envContent);
            $this->info('.env atualizado com as novas chaves VAPID.');
        }

        return self::SUCCESS;
    }
}

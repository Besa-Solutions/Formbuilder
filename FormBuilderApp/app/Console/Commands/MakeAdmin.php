<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-admin {email? : The email of the user to promote} {--create : Create a new admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user or promote an existing user to admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('create')) {
            $this->createAdmin();
            return;
        }

        $email = $this->argument('email');
        
        if (!$email) {
            $email = $this->ask('Enter the email of the user to promote to admin');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            if ($this->confirm('Do you want to create a new admin user instead?', true)) {
                $this->createAdmin();
            }
            return;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("User {$user->name} has been promoted to admin role.");
    }

    /**
     * Create a new admin user.
     */
    protected function createAdmin()
    {
        $name = $this->ask('Enter the name for the new admin');
        $email = $this->ask('Enter the email for the new admin');
        $password = $this->secret('Enter the password for the new admin');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info("Admin user {$user->name} has been created successfully.");
    }
}

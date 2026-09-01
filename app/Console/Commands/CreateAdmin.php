<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\Dasher;

class CreateAdmin extends Command
{
    // This is the command you type in the terminal
    protected $signature = 'make:admin';

    // use 'php artisan' to terminal, and find command on make:
    protected $description = 'Creates a new administrator via the terminal';

    public function handle()
    {
        // set admin email
        $email = $this->ask('Email for the admin?');

        // Check if user already exists
        if (Dasher::where('email', $email)->exists()) {
            $this->error('A user with this email already exists!');
            return;
        }

        // this->secret method will not show while typing
        $password = $this->secret('Password for the admin?');
        // Confirmation check (optional but helpful)
        if (empty($password)) {
            $this->error('Password cannot be empty!');
            $this->secret('Password for admin?');
        }

        $first = $this->ask("First name");
        if (empty($first)) {
            $this->error('First Name cannot be empty!');
            return;
        }

        $last = $this->ask("Last name");

        if (empty($last)) {
            $this->error('Last Name cannot be empty!');
            return;
        }

        // add news admin
        Dasher::create([
            'first_name' => $first, // depende unsa first name
            'last_name' => $last, // depende unsa last name
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin', // Ensure 'role' is in your User model's $fillable array!
        ]);

        $this->info("Admin account ($email) created successfully!");
    }
}

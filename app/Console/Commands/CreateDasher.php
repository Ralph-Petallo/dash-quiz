<?php

namespace App\Console\Commands;

use App\Models\Dasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Console\Command;

class CreateDasher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dasher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new dasher account via the terminal';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $first_name = $this->ask('Enter first name: ');
        $last_name = $this->ask('Enter last name: ');
        $email = $this->ask('Enter email account: ');
        $password = $this->secret('Enter password for account');


        // Check if user already exists
        if (Dasher::where('email', $email)->exists()) {
            $this->error('Error: A user with this email already exists!');
            return;
        }

        // Confirmation check (optional but helpful)
        if (empty($password)) {
            $this->error('Error: Password cannot be empty!');
            return;
        }

        // add news admin
        Dasher::create([
            'first_name' => $first_name, // depende unsa first name
            'last_name' => $last_name, // depende unsa last name
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'dasher', // Ensure 'role' is in your User model's $fillable array!
        ]);

        $this->info("Dasher account ($email) has created successfully!");
    }
}

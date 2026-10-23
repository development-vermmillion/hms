<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateHotel extends Command
{
    protected $signature = 'hms:create-hotel
        {name : Hotel / client name}
        {owner_email : Login e-mail of the hotel owner}
        {--owner-name= : Owner display name}
        {--password= : Owner password (prompted if omitted)}';

    protected $description = 'Onboard a new hotel (client) with its owner account and default room types';

    public function handle(): int
    {
        $email = $this->argument('owner_email');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Owner password (min 8 chars)');
        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password) {
            $name = $this->argument('name');
            $code = Str::slug($name);
            $suffix = 1;
            while (Hotel::where('code', $code)->exists()) {
                $code = Str::slug($name).'-'.(++$suffix);
            }

            $hotel = Hotel::create(['name' => $name, 'code' => $code]);

            User::create([
                'hotel_id' => $hotel->id,
                'name' => $this->option('owner-name') ?: 'Owner',
                'email' => $email,
                'employee_code' => 'OWN001',
                'role' => User::OWNER,
                'password' => $password,
            ]);

            foreach ([['Single', 1500, 1], ['Double', 2500, 2], ['Deluxe', 3800, 3], ['Suite', 6500, 4]] as [$type, $price, $cap]) {
                RoomType::create(['hotel_id' => $hotel->id, 'name' => $type, 'base_price' => $price, 'capacity' => $cap]);
            }
        });

        $this->info("Hotel created. Owner can sign in with {$email} (staff ID OWN001).");

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\AmenityItem;
use App\Models\FoodItem;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Demo data: one hotel, an owner, a receptionist, rooms, menu and amenities. */
    public function run(): void
    {
        $hotel = Hotel::create([
            'name' => 'Demo Grand Hotel',
            'code' => 'demo-grand',
            'address' => '12 MG Road',
            'city' => 'Gurugram',
            'phone' => '+91 99999 00000',
            'email' => 'frontdesk@demo-grand.test',
            'tax_percent' => 12,
            'invoice_footer' => 'Thank you for staying with us.',
        ]);

        User::create([
            'hotel_id' => $hotel->id, 'name' => 'Demo Owner', 'email' => 'owner@hms.test',
            'employee_code' => 'OWN001', 'role' => User::OWNER, 'password' => 'password',
        ]);
        User::create([
            'hotel_id' => $hotel->id, 'name' => 'Demo Receptionist', 'email' => 'staff@hms.test',
            'employee_code' => 'EMP001', 'role' => User::STAFF, 'password' => 'password',
        ]);

        $types = [];
        foreach ([['Single', 1500, 1], ['Double', 2500, 2], ['Deluxe', 3800, 3], ['Suite', 6500, 4]] as [$name, $price, $cap]) {
            $types[$name] = RoomType::create([
                'hotel_id' => $hotel->id, 'name' => $name, 'base_price' => $price, 'capacity' => $cap,
            ]);
        }

        $layout = ['Single' => 3, 'Double' => 4, 'Deluxe' => 3, 'Suite' => 2];
        $floor = 1;
        $n = 100;
        foreach ($layout as $typeName => $count) {
            for ($i = 0; $i < $count; $i++) {
                $n++;
                Room::create([
                    'hotel_id' => $hotel->id,
                    'room_type_id' => $types[$typeName]->id,
                    'room_number' => (string) $n,
                    'floor' => (string) $floor,
                    'capacity' => $types[$typeName]->capacity,
                    'price' => $types[$typeName]->base_price,
                ]);
            }
            $floor++;
            $n = $floor * 100;
        }

        foreach ([['Bath towel', 250], ['Hand towel', 120], ['Electric kettle', 900], ['Slippers', 150], ['Plates (set)', 400], ['Cups (set)', 300]] as [$name, $price]) {
            AmenityItem::create(['hotel_id' => $hotel->id, 'name' => $name, 'replacement_price' => $price]);
        }

        foreach ([['Masala Tea', 60], ['Filter Coffee', 80], ['Veg Sandwich', 180], ['Paneer Butter Masala', 320], ['Dal Tadka + Rice', 240], ['Club Sandwich', 260], ['Mineral Water', 40]] as [$name, $price]) {
            FoodItem::create(['hotel_id' => $hotel->id, 'name' => $name, 'price' => $price]);
        }
    }
}

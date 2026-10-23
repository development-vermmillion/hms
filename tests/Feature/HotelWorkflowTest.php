<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Stay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function hotelWithStaff(string $name, string $email, string $code = 'EMP001', string $role = User::STAFF): array
    {
        $hotel = Hotel::create(['name' => $name, 'code' => str($name)->slug()->toString()]);
        $user = User::create([
            'hotel_id' => $hotel->id, 'name' => 'Tester', 'email' => $email,
            'employee_code' => $code, 'role' => $role, 'password' => 'password',
        ]);
        $type = RoomType::create(['hotel_id' => $hotel->id, 'name' => 'Double', 'base_price' => 2000, 'capacity' => 2]);
        $room = Room::create(['hotel_id' => $hotel->id, 'room_type_id' => $type->id, 'room_number' => '101', 'price' => 2000]);

        return [$hotel, $user, $room];
    }

    public function test_guests_must_log_in(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_a_hotel_never_sees_another_hotels_data(): void
    {
        [, $userA] = $this->hotelWithStaff('Hotel A', 'a@test.dev');
        $this->hotelWithStaff('Hotel B', 'b@test.dev');

        $this->actingAs($userA);

        $this->assertSame(1, Room::count());
        $this->assertSame($userA->hotel_id, Room::first()->hotel_id);
    }

    public function test_staff_cannot_open_owner_pages(): void
    {
        [, $staff] = $this->hotelWithStaff('Hotel A', 'a@test.dev');

        $this->actingAs($staff)->get('/settings')->assertForbidden();
        $this->actingAs($staff)->get('/reports')->assertForbidden();
    }

    public function test_entries_require_the_matching_staff_id(): void
    {
        [, $staff] = $this->hotelWithStaff('Hotel A', 'a@test.dev', 'EMP007');

        $payload = ['name' => 'Asha', 'phone' => '9999999999'];

        $this->actingAs($staff)->post('/guests', $payload + ['staff_code' => 'WRONG'])
            ->assertSessionHasErrors('staff_code');
        $this->assertSame(0, Guest::count());

        $this->actingAs($staff)->post('/guests', $payload + ['staff_code' => 'emp007'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Asha', Guest::first()->name);
        $this->assertSame($staff->id, Guest::first()->created_by);
    }

    public function test_check_in_to_check_out_flow_bills_penalties_and_frees_the_room(): void
    {
        [$hotel, $staff, $room] = $this->hotelWithStaff('Hotel A', 'a@test.dev', 'EMP001', User::OWNER);
        $hotel->update(['tax_percent' => 10]);
        $item = \App\Models\AmenityItem::create(['hotel_id' => $hotel->id, 'name' => 'Kettle', 'replacement_price' => 500]);

        $this->actingAs($staff)->post('/stays', [
            'guest_name' => 'Ravi', 'guest_phone' => '8888888888', 'id_proof_type' => 'Aadhaar', 'id_proof_number' => '1234',
            'room_id' => $room->id, 'arrival_date' => today()->toDateString(),
            'expected_check_out' => today()->addDays(2)->toDateString(),
            'adults' => 1, 'rate' => 2000, 'advance' => 1000, 'action' => 'check_in', 'staff_code' => 'EMP001',
        ])->assertSessionHasNoErrors();

        $stay = Stay::first();
        $this->assertSame('occupied', $room->fresh()->status);

        $this->post("/stays/{$stay->id}/amenities", ['amenity_item_id' => $item->id, 'qty_issued' => 1, 'staff_code' => 'EMP001']);
        $amenityId = $stay->amenities()->first()->id;

        $this->post("/stays/{$stay->id}/checkout", ['returns' => [$amenityId => 0], 'staff_code' => 'EMP001'])
            ->assertSessionHasNoErrors();

        $invoice = $stay->fresh()->invoice;
        // Checked out the same day: 1 night x 2000 = 2000, tax 10% = 200, missing kettle = 500 -> 2700, advance 1000 paid.
        $this->assertEquals(2700.00, (float) $invoice->total);
        $this->assertEquals(1000.00, (float) $invoice->paid);
        $this->assertEquals(1700.00, (float) $invoice->balance);
        $this->assertSame('partial', $invoice->status);
        $this->assertSame('cleaning', $room->fresh()->status);
        $this->assertDatabaseHas('housekeeping_tasks', ['room_id' => $room->id, 'status' => 'pending']);
    }
}

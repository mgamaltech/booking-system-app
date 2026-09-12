<?php

namespace Tests\Unit;

use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_confirmation_job_is_pushed_when_booking_is_updated()
    {
        Event::fake([BookingConfirmed::class]);

        $booking = Booking::factory()->create(['status' => 'pending']);
        $this->actingAs(Customer::query()->findOrFail($booking->customer_id), 'sanctum')
            ->post(route('bookings.update', $booking), ['status' => 'confirmed'])
            ->assertOk();

        Event::assertDispatched(BookingConfirmed::class, function (BookingConfirmed $event) use ($booking) {
            return $event->bookingId === $booking->id
                && $event->fromStatus === $booking->status
                && $event->toStatus === 'confirmed';
        });

    }

    public function test_booking_confirmation_job_is_not_pushed_when_booking_is_not_confirmed()
    {
        Event::fake([BookingConfirmed::class]);

        $booking = Booking::factory()->create(['status' => 'pending']);
        $this->actingAs(Customer::query()->findOrFail($booking->customer_id), 'sanctum')
            ->post(route('bookings.update', $booking), ['status' => 'pending']);

        Event::assertNotDispatched(BookingConfirmed::class);
    }

    public function test_booking_confirmation_job_is_marked_to_dispatch_after_commit(): void
    {
        Event::fake([BookingConfirmed::class]);

        $booking = Booking::factory()->create([
            'status' => 'pending',
        ]);

        $this->actingAs(Customer::query()->findOrFail($booking->customer_id), 'sanctum')
            ->post(route('bookings.update', $booking), [
                'status' => 'confirmed',
            ]);

        Event::assertDispatched(BookingConfirmed::class);
    }
}

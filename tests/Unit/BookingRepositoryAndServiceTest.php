<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Resource;
use App\Models\Slot;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookingRepositoryAndServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Resource $resource;

    protected Customer $customer;

    protected Slot $slot;

    protected array $bookingData;

    protected BookingRepositoryInterface $bookingRepository;

    /**
     * A basic feature test example.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');

        $this->resource = Resource::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->slot = Slot::factory()->create();
        $this->bookingData = [
            'customer_id' => $this->customer->id,
            'resource_id' => $this->resource->id,
            'slot_id' => $this->slot->id,
            'status' => 'pending',
        ];

        $this->bookingRepository = $this->app->make(BookingRepositoryInterface::class);
    }

    public function test_booking_repository_is_injected_correctly(): void
    {
        $this->assertInstanceOf(BookingRepositoryInterface::class, $this->bookingRepository);
    }

    public function test_booking_service_can_create_booking(): void
    {
        $service = app(BookingService::class);
        $this->app->instance(BookingService::class, $service);
        $booking = $service->createBooking($this->bookingData);
        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseHas('bookings', $this->bookingData);
    }

    public function test_booking_repository_can_create_booking(): void
    {
        $booking = $this->bookingRepository->create($this->bookingData);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseHas('bookings', $this->bookingData);
    }

    public function test_booking_repository_can_find_booking_by_id(): void
    {
        $booking = Booking::factory()->create($this->bookingData);

        $foundBooking = $this->bookingRepository->find($booking->id);

        $this->assertTrue($foundBooking->is($booking));
    }

    public function test_booking_repository_can_find_booking_by_column(): void
    {
        Booking::factory()->create($this->bookingData);
        $confirmedBooking = Booking::factory()->create(
            array_merge($this->bookingData, ['status' => 'confirmed']
            ));

        $foundBooking = $this->bookingRepository->findBy('status', 'confirmed');

        $this->assertTrue($foundBooking->is($confirmedBooking));
    }

    public function test_booking_repository_can_paginate_all_bookings(): void
    {
        Booking::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'resource_id' => $this->resource->id,
            'slot_id' => $this->slot->id,
        ]);

        $bookings = $this->bookingRepository->all();

        $this->assertInstanceOf(LengthAwarePaginator::class, $bookings);
        $this->assertSame(3, $bookings->total());
    }

    public function test_booking_repository_can_update_booking(): void
    {
        $booking = Booking::factory()->create($this->bookingData);

        $updated = $this->bookingRepository->update(['status' => 'confirmed'], $booking->id);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_booking_repository_can_delete_booking(): void
    {
        $booking = Booking::factory()->create($this->bookingData);

        $deleted = $this->bookingRepository->delete($booking->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('bookings', [
            'id' => $booking->id,
        ]);
        $this->expectException(ModelNotFoundException::class);

        $this->bookingRepository->find($booking->id);
    }

    public function test_booking_service_can_update_booking(): void
    {
        $booking = Booking::factory()->create($this->bookingData);
        $service = app(BookingService::class);

        $updated = $service->updateBooking(['status' => 'confirmed'], $booking->id);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_booking_service_can_delete_booking(): void
    {
        $booking = Booking::factory()->create($this->bookingData);
        $service = app(BookingService::class);

        $deleted = $service->deleteBooking($booking->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_booking_service_can_get_all_bookings(): void
    {
        Booking::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'resource_id' => $this->resource->id,
            'slot_id' => $this->slot->id,
        ]);
        $service = app(BookingService::class);

        $bookings = $service->getAllBookings();

        $this->assertInstanceOf(LengthAwarePaginator::class, $bookings);
        $this->assertSame(2, $bookings->total());
    }

    public function test_booking_service_can_get_booking_by_id(): void
    {
        $booking = Booking::factory()->create($this->bookingData);
        $service = app(BookingService::class);

        $foundBooking = $service->getBookingById($booking->id);

        $this->assertTrue($foundBooking->is($booking));
    }
}

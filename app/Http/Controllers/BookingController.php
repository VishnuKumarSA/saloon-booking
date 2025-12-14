<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Beautician;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        return view('booking');
    }

    public function slots(Request $request)
    {
        $events = [];

        $startDate = Carbon::parse($request->start)->startOfDay();
        $endDate = Carbon::parse($request->end)->startOfDay();
        
        // Use Asia/Kolkata timezone (or your specific timezone)
        $now = Carbon::now('Asia/Kolkata');

        while ($startDate < $endDate) {

            // Skip PAST days only (days before today, not including today)
            if ($startDate->lt($now->copy()->startOfDay())) {
                $startDate->addDay();
                continue;
            }

            // Skip Friday (Holiday)
            if ($startDate->isFriday()) {
                $startDate->addDay();
                continue;
            }

            // Loop through hours: 9 AM to 5 PM (last slot is 5 PM)
            for ($hour = 9; $hour < 18; $hour++) {

                // Create the exact datetime for this slot in Asia/Kolkata timezone
                $slotDateTime = Carbon::create(
                    $startDate->year,
                    $startDate->month,
                    $startDate->day,
                    $hour,
                    0,
                    0,
                    'Asia/Kolkata'
                );

                // Check if this slot has passed (slot time is less than or equal to current time)
                $isPastSlot = $slotDateTime->lte($now);

                // Count bookings for this slot
                $booked = Booking::whereDate('booking_date', $startDate->toDateString())
                    ->where('start_time', sprintf('%02d:00:00', $hour))
                    ->count();

                $available = max(0, 5 - $booked);

                // Determine slot state
                if ($isPastSlot) {
                    // Past slots - gray, disabled
                    $className = 'past-slot';
                    $displayAvailable = 0;
                    $isDisabled = true;
                } elseif ($available === 0) {
                    // Fully booked - red
                    $className = 'fully-booked';
                    $displayAvailable = 0;
                    $isDisabled = false;
                } elseif ($available <= 2) {
                    // Almost full - orange/warning (1 or 2 slots left)
                    $className = 'available almost-full';
                    $displayAvailable = $available;
                    $isDisabled = false;
                } else {
                    // Available - green (3+ slots)
                    $className = 'available';
                    $displayAvailable = $available;
                    $isDisabled = false;
                }

                $events[] = [
                    'title' => $slotDateTime->format('h:i A'),
                    'start' => $slotDateTime->format('Y-m-d\TH:i:s'),
                    'classNames' => [$className],
                    'extendedProps' => [
                        'available' => $displayAvailable,
                        'disabled' => $isDisabled,
                    ],
                ];
            }

            $startDate->addDay();
        }

        return response()->json($events);
    }

    public function store(Request $request)
    {
        // Validate inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'date' => 'required|date',
            'time' => 'required',
            'beautician_id' => 'required|exists:beauticians,id'
        ]);

        // Validate the booking date and time are not in the past
        $bookingDateTime = Carbon::parse($request->date . ' ' . $request->time);

        if ($bookingDateTime->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot book past dates/times'
            ], 400);
        }

        // Check if slot is fully booked
        $booked = Booking::whereDate('booking_date', $request->date)
            ->where('start_time', $request->time)
            ->count();

        if ($booked >= 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'This slot is fully booked'
            ], 400);
        }

        // Check if selected beautician is available
        $beauticianBooked = Booking::whereDate('booking_date', $request->date)
            ->where('start_time', $request->time)
            ->where('beautician_id', $request->beautician_id)
            ->exists();

        if ($beauticianBooked) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selected beautician is not available for this slot'
            ], 400);
        }

        // Create booking
        Booking::create([
            'customer_name' => $request->name,
            'mobile' => $request->mobile,
            'booking_date' => $request->date,
            'start_time' => $request->time,
            'end_time' => Carbon::parse($request->time)->addHour(),
            'beautician_id' => $request->beautician_id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking confirmed successfully!'
        ]);
    }

    public function availableBeauticians(Request $request)
    {
        $date = $request->date;
        $time = $request->time;

        // Validate not booking past dates
        $bookingDateTime = Carbon::parse($date . ' ' . $time);

        if ($bookingDateTime->isPast()) {
            return response()->json([]);
        }

        // Get beauticians who are already booked at this time
        $booked = Booking::whereDate('booking_date', $date)
            ->where('start_time', $time)
            ->pluck('beautician_id');

        // Return available beauticians
        return Beautician::whereNotIn('id', $booked)->get();
    }
}
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

        $now = Carbon::now('Asia/Kolkata');

        while ($startDate < $endDate) {

            // Friday = Holiday
            if ($startDate->isFriday()) {
                $events[] = [
                    'title' => 'Holiday',
                    'start' => $startDate->copy()->setTime(7, 0)->format('Y-m-d\TH:i:s'),
                    'end' => $startDate->copy()->setTime(22, 0)->format('Y-m-d\TH:i:s'),                  
                    'classNames' => ['holiday-bg'],
                    'extendedProps' => [
                        'holiday' => true,
                    ],
                ];

                $startDate->addDay();
                continue;
            }

            // Slots 9 AM – 6 PM
            for ($hour = 7; $hour < 22; $hour++) {

                $slotDateTime = Carbon::create(
                    $startDate->year,
                    $startDate->month,
                    $startDate->day,
                    $hour,
                    0,
                    0,
                    'Asia/Kolkata'
                );

                $isPastSlot = $slotDateTime->lte($now);

                $booked = Booking::whereDate('booking_date', $startDate->toDateString())
                    ->where('start_time', sprintf('%02d:00:00', $hour))
                    ->count();

                $available = max(0, 5 - $booked);

                if ($isPastSlot) {
                    $className = 'past-slot';
                    $displayAvailable = 0;
                    $isDisabled = true;
                } elseif ($available === 0) {
                    $className = 'fully-booked';
                    $displayAvailable = 0;
                    $isDisabled = false;
                } elseif ($available <= 2) {
                    $className = 'available almost-full';
                    $displayAvailable = $available;
                    $isDisabled = false;
                } else {
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

        $checkDuplicateCustomer = Booking::whereDate('booking_date', $request->date)
            ->where('start_time', $request->time)
            ->where('mobile', $request->mobile)
            ->exists();

        if ($checkDuplicateCustomer) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have a booking for this time slot'
            ], 400);
        }

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
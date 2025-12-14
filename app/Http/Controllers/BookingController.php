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
        $now = Carbon::now();

        while ($startDate < $endDate) {

            // ❌ Skip past dates (before today)
            if ($startDate->lt(Carbon::today())) {
                $startDate->addDay();
                continue;
            }

            // ❌ Friday = Holiday
            if ($startDate->isFriday()) {
                $startDate->addDay();
                continue;
            }

            $hasFutureSlot = false; // 🔑 IMPORTANT FLAG

            $slotTime = Carbon::createFromTime(9, 0);
            $endTime = Carbon::createFromTime(18, 0);

            while ($slotTime < $endTime) {

                $slotDateTime = Carbon::create(
                    $startDate->year,
                    $startDate->month,
                    $startDate->day,
                    $slotTime->hour,
                    $slotTime->minute,
                    0
                );

                // ❌ Skip past time slots for TODAY
                if ($slotDateTime->lte($now)) {
                    $slotTime->addHour();
                    continue;
                }

                $hasFutureSlot = true;

                $booked = Booking::whereDate(
                    'booking_date',
                    $startDate->toDateString()
                )->where(
                        'start_time',
                        $slotTime->format('H:i:s')
                    )->count();

                $available = max(0, 5 - $booked);

                $events[] = [
                    'title' => $available === 0
                        ? 'CLOSED'
                        : "$available/5 Available",

                    // ✅ Correct ISO local time
                    'start' => $startDate->format('Y-m-d') . 'T' . $slotTime->format('H:i:s'),

                    'classNames' => [
                        $available === 0 ? 'fully-booked' : 'available'
                    ],

                    'extendedProps' => [
                        'available' => $available
                    ]
                ];

                $slotTime->addHour();
            }

            // ✅ FORCE TODAY TO APPEAR
            if ($startDate->isToday() && !$hasFutureSlot) {
                $events[] = [
                    'title' => 'No slots available today',
                    'start' => $startDate->format('Y-m-d') . 'T12:00:00',
                    'allDay' => true,
                    'classNames' => ['fully-booked'],
                    'extendedProps' => [
                        'available' => 0
                    ]
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
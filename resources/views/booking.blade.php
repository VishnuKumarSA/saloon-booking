@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h4 class="mb-3">Weekly Booking</h4>
        <div id="calendar"></div>
    </div>

    <div class="modal fade" id="bookingModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content p-3">
                <h6 class="text-center mb-2">Book Appointment</h6>

                <input id="name" class="form-control mb-2" placeholder="Customer Name" required>
                <input id="mobile" class="form-control mb-2" placeholder="Mobile" required>

                <select id="beautician" class="form-control mb-3" required>
                    <option value="">Select Beautician</option>
                </select>

                <button id="confirmBooking" class="btn btn-success w-100">
                    Book
                </button>
            </div>
        </div>
    </div>

    <style>
        /* ========== WEEK/DAY TIME GRID VIEW ========== */
        .fc-timegrid-slot {
            height: 80px !important;
        }

        .fc-timegrid-event {
            min-height: 70px !important;
        }

        .fc-timegrid-event .fc-event-main {
            padding: 10px 5px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }

        /* ========== MONTH VIEW ========== */
        .fc-daygrid-day {
            min-height: 180px !important;
        }

        .fc-daygrid-day-frame {
            min-height: 180px !important;
            height: auto !important;
        }

        .fc-daygrid-day-events {
            min-height: 140px !important;
        }

        .fc-daygrid-event {
            padding: 6px 8px !important;
            margin: 3px 4px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            border-radius: 4px !important;
            white-space: normal !important;
            height: auto !important;
        }

        /* ========== LIST VIEW STYLES ========== */
        .fc-list {
            background: #fff !important;
        }

        .fc-list-day-cushion {
            background: #f8f9fa !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 10px 15px !important;
            border-bottom: 2px solid #dee2e6 !important;
        }

        .fc-list-table {
            border: none !important;
        }

        .fc-list-event {
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .fc-list-event td {
            padding: 15px !important;
            border-bottom: 1px solid #eee !important;
        }

        .fc-list-event-time {
            font-weight: 700 !important;
            font-size: 16px !important;
            min-width: 100px !important;
            color: #333 !important;
        }

        .fc-list-event-graphic {
            padding-right: 15px !important;
        }

        .fc-list-event-dot {
            width: 14px !important;
            height: 14px !important;
            border-width: 3px !important;
        }

        .fc-list-event-title {
            font-weight: 600 !important;
            font-size: 15px !important;
            padding-left: 10px !important;
        }

        /* List view - Available */
        .fc-list-event.available {
            background-color: #f5fff5 !important;
        }

        .fc-list-event.available:hover {
            background-color: #e6ffe6 !important;
        }

        .fc-list-event.available .fc-list-event-dot {
            border-color: #28a745 !important;
            background-color: #28a745 !important;
        }

        .fc-list-event.available .fc-list-event-title {
            color: #28a745 !important;
            font-weight: 700 !important;
        }

        .fc-list-event.available .fc-list-event-time {
            color: #28a745 !important;
        }

        /* List view - Fully Booked */
        .fc-list-event.fully-booked {
            background-color: #fff5f5 !important;
        }

        .fc-list-event.fully-booked:hover {
            background-color: #ffe6e6 !important;
            cursor: not-allowed !important;
        }

        .fc-list-event.fully-booked .fc-list-event-dot {
            border-color: #dc3545 !important;
            background-color: #dc3545 !important;
        }

        .fc-list-event.fully-booked .fc-list-event-title {
            color: #dc3545 !important;
            font-weight: 700 !important;
        }

        .fc-list-event.fully-booked .fc-list-event-time {
            color: #dc3545 !important;
        }

        /* ========== GRID VIEWS (WEEK/DAY/MONTH) ========== */
        /* Available slots - GREEN */
        .fc-event.available {
            background-color: #28a745 !important;
            border: 2px solid #1e7e34 !important;
            color: #ffffff !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            font-size: 13px !important;
        }

        .fc-event.available .fc-event-main {
            color: #ffffff !important;
        }

        .fc-event.available .fc-event-title {
            color: #ffffff !important;
        }

        .fc-event.available:hover {
            background-color: #218838 !important;
            transform: scale(1.02);
            transition: all 0.2s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Fully booked slots - RED */
        .fc-event.fully-booked {
            background-color: #dc3545 !important;
            border: 2px solid #a71d2a !important;
            color: #ffffff !important;
            pointer-events: none !important;
            opacity: 0.9;
            cursor: not-allowed !important;
            font-weight: bold !important;
            font-size: 13px !important;
        }

        .fc-event.fully-booked .fc-event-main {
            color: #ffffff !important;
        }

        .fc-event.fully-booked .fc-event-title {
            color: #ffffff !important;
        }

        /* ========== GENERAL STYLES ========== */
        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            color: #333 !important;
        }

        .fc-button {
            font-weight: 600 !important;
            text-transform: capitalize !important;
        }

        .fc-day-today {
            background-color: #fff9e6 !important;
        }

        .fc-event-title {
            color: inherit !important;
            font-weight: inherit !important;
        }

        /* Hide resize handles */
        .fc-event-resizer {
            display: none !important;
        }

        /* Highlight current day column */
        .fc-day-today {
            background-color: #fffbe6 !important;
        }

        /* Fix event alignment padding */
        .fc-timegrid-event {
            margin: 4px 6px !important;
        }

        /* Improve vertical alignment */
        .fc-timegrid-event .fc-event-main {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .fc-event.available {
            background: linear-gradient(135deg, #28a745, #34ce57) !important;
            border: 2px solid #1e7e34 !important;
        }

        .fc-event.available small {
            font-size: 12px;
            opacity: 0.9;
        }

        .fc-event.fully-booked {
            background: linear-gradient(135deg, #dc3545, #e4606d) !important;
        }
    </style>

    <script>
        let selected;
        let modalInstance;

        document.addEventListener('DOMContentLoaded', function () {

            let calendar = new FullCalendar.Calendar(
                document.getElementById('calendar'), {

                initialView: 'timeGridWeek',
                height: 'auto',
                contentHeight: 'auto',
                expandRows: true,
                timeZone: 'local',
                slotLabelFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                },

                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                },

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },

                slotMinTime: '09:00:00',
                slotMaxTime: '18:00:00',
                slotDuration: '01:00:00',
                allDaySlot: false,

                // Set initial date to today
                initialDate: new Date(),

                // Show current time indicator
                nowIndicator: true,

                events: function (info, success, failure) {
                    $.get('/slots', {
                        start: info.startStr,
                        end: info.endStr
                    })
                        .done(function (events) {
                            console.log('Loaded events:', events);
                            success(events);
                        })
                        .fail(function (error) {
                            console.error('Error loading events:', error);
                            failure(error);
                        });
                },

                eventDidMount: function (info) {

                    const available = info.event.extendedProps.available ?? 0;
                    const view = info.view.type;

                    // WEEK & DAY VIEW
                    if (view.startsWith('timeGrid')) {
                        const main = info.el.querySelector('.fc-event-main');

                        if (main) {
                            main.innerHTML = available === 0
                                ? `<div>
                            <strong>CLOSED</strong><br>
                            <small>No slots</small>
                       </div>`
                                : `<div>
                            <strong>${available}/5 Available</strong><br>
                            <small>Click to book</small>
                       </div>`;
                        }
                    }

                    // LIST VIEW
                    if (view.startsWith('list')) {
                        const title = info.el.querySelector('.fc-list-event-title');
                        title.textContent = available === 0
                            ? info.event.title
                            : `${available} Slots Available`;
                    }
                }
                ,

                eventClick: function (info) {
                    const available = info.event.extendedProps.available || 0;

                    // Don't allow booking if fully booked
                    if (available === 0) {
                        alert('This slot is fully booked. Please select another time.');
                        return;
                    }

                    // Don't allow booking past dates/times
                    const now = new Date();
                    if (info.event.start < now) {
                        alert('Cannot book past time slots.');
                        return;
                    }

                    selected = info.event.start;
                    loadBeauticians();
                }
            });

            calendar.render();

            $('#confirmBooking').click(function () {

                // Validate inputs
                const name = $('#name').val().trim();
                const mobile = $('#mobile').val().trim();
                const beauticianId = $('#beautician').val();

                if (!name) {
                    alert('Please enter customer name');
                    return;
                }

                if (!mobile) {
                    alert('Please enter mobile number');
                    return;
                }

                if (!beauticianId) {
                    alert('Please select a beautician');
                    return;
                }

                // Disable button to prevent double submission
                $(this).prop('disabled', true).text('Booking...');

                $.post('/book', {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    mobile: mobile,
                    beautician_id: beauticianId,
                    date: selected.toISOString().split('T')[0],
                    time: selected.toTimeString().substring(0, 8)
                })
                    .done(function (response) {
                        if (response.status === 'success') {
                            alert('Booking confirmed successfully!');
                            location.reload();
                        } else {
                            alert('Booking failed: ' + (response.message || 'Unknown error'));
                            $('#confirmBooking').prop('disabled', false).text('Book');
                        }
                    })
                    .fail(function (xhr) {
                        const error = xhr.responseJSON;
                        alert('Booking failed: ' + (error?.message || 'Please try again'));
                        $('#confirmBooking').prop('disabled', false).text('Book');
                    });
            });
        });

        function loadBeauticians() {

            const date = selected.toISOString().split('T')[0];
            const time = selected.toTimeString().substring(0, 8);

            $.get('/available-beauticians', { date, time }, function (res) {

                $('#beautician').empty()
                    .append('<option value="">Select Beautician</option>');

                if (res.length === 0) {
                    $('#beautician').append(
                        '<option disabled>No beautician available</option>'
                    );
                    alert('No beauticians available for this slot. Please choose another time.');
                    return;
                }

                res.forEach(b => {
                    $('#beautician').append(
                        `<option value="${b.id}">${b.name}</option>`
                    );
                });

                // Clear previous values
                $('#name').val('');
                $('#mobile').val('');
                $('#confirmBooking').prop('disabled', false).text('Book');

                // Show modal
                if (modalInstance) {
                    modalInstance.hide();
                }
                modalInstance = new bootstrap.Modal(
                    document.getElementById('bookingModal')
                );
                modalInstance.show();
            });
        }
    </script>
@endsection
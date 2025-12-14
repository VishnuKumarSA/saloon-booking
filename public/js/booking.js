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
            const disabled = info.event.extendedProps.disabled ?? false;
            const viewType = info.view.type;

            if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {

                const main = info.el.querySelector('.fc-event-main');
                if (!main) return;

                if (disabled) {
                    main.innerHTML = `
                <div class="text-center">
                    <strong>${info.event.title}</strong><br>
                    <small>Past</small>
                </div>
            `;
                    info.el.style.pointerEvents = 'none';
                    info.el.style.cursor = 'not-allowed';
                }
                else if (available === 0) {
                    main.innerHTML = `
                <div class="text-center">
                    <strong>${info.event.title}</strong><br>
                    <small>Fully booked</small>
                </div>
            `;
                    info.el.style.pointerEvents = 'none';
                    info.el.style.cursor = 'not-allowed';
                }
                else if (available <= 2) {
                    // Almost full - show warning
                    main.innerHTML = `
                <div class="text-center">
                    <strong>${info.event.title}</strong><br>
                    <span class="text-warning">⚠️ ${available}/5 Available</span><br>
                    <small>Book now!</small>
                </div>
            `;
                    // Add pulsing animation class
                    info.el.classList.add('almost-full');
                }
                else {
                    main.innerHTML = `
                <div class="text-center">
                    <strong>${info.event.title}</strong><br>
                    <span>${available}/5 Available</span><br>
                    <small>Click to book</small>
                </div>
            `;
                }
            }

            // List view
            if (viewType.startsWith('list')) {
                const titleEl = info.el.querySelector('.fc-list-event-title');
                if (!titleEl) return;

                if (disabled) {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - Past`;
                    info.el.style.pointerEvents = 'none';
                    info.el.style.cursor = 'not-allowed';
                } else if (available === 0) {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - Fully booked`;
                    info.el.style.pointerEvents = 'none';
                    info.el.style.cursor = 'not-allowed';
                } else if (available <= 2) {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - ⚠️ Only ${available}/5 Available - Book now!`;
                    info.el.classList.add('almost-full');
                } else {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - ${available}/5 Available`;
                }
            }
        },


        eventClick: function (info) {
            const available = info.event.extendedProps.available || 0;
            const disabled = info.event.extendedProps.disabled || false;

            // Don't allow booking disabled/past slots
            if (disabled) {
                alert('Cannot book past time slots.');
                return;
            }

            // Don't allow booking if fully booked
            if (available === 0) {
                alert('This slot is fully booked. Please select another time.');
                return;
            }

            // Double-check if slot is in the past
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
            _token: $('meta[name="csrf-token"]').attr('content'),
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
        const modalEl = document.getElementById('bookingModal');
        if (!modalEl) {
            alert('Booking modal not found. Please refresh the page.');
            return;
        }
        
        if (modalInstance) {
            modalInstance.dispose();
        }
        
        modalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });
        modalInstance.show();
    });
}
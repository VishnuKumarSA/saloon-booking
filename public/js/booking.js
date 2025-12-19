let selected;
let modalInstance;

document.addEventListener('DOMContentLoaded', function () {

    let calendar = new FullCalendar.Calendar(
    document.getElementById('calendar'), {

    initialView: 'timeGridWeek',
    height: 'auto',
    expandRows: true,
    timeZone: 'local',

    slotMinTime: '09:00:00',
    slotMaxTime: '18:00:00',
    slotDuration: '01:00:00',
    allDaySlot: false,

    initialDate: new Date(),
    nowIndicator: true,

    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },

    events: function (info, success, failure) {
        $.get('/slots', {
            start: info.startStr,
            end: info.endStr
        })
        .done(success)
        .fail(failure);
    },

    eventDidMount: function (info) {

        const available = info.event.extendedProps.available ?? 0;
        const disabled  = info.event.extendedProps.disabled ?? false;
        const isHoliday = info.event.extendedProps.holiday ?? false;
        const viewType  = info.view.type;

        /* ========= HOLIDAY LABEL ========= */
        if (isHoliday) {
            const col = info.el.closest('.fc-timegrid-col');
            if (col && !col.querySelector('.holiday-label')) {
                const label = document.createElement('div');
                label.className = 'holiday-label';
                label.innerText = 'Holiday';
                col.style.position = 'relative';
                col.appendChild(label);
            }
            return; // no further rendering
        }

        /* ========= PAST SLOT ========= */
        if (disabled) {
            const main = info.el.querySelector('.fc-event-main');
            if (main) {
                main.innerHTML = `
                    <div class="text-center">
                        <strong>${info.event.title}</strong><br>
                        <small>Past</small>
                    </div>
                `;
            }
            info.el.style.pointerEvents = 'none';
            info.el.style.cursor = 'not-allowed';
            return;
        }

        /* ========= TIME GRID ========= */
        if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {

            const main = info.el.querySelector('.fc-event-main');
            if (!main) return;

            if (available === 0) {
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
                main.innerHTML = `
                    <div class="text-center">
                        <strong>${info.event.title}</strong><br>
                        <span class="text-warning">⚠️ ${available}/5 Available</span><br>
                        <small>Book now!</small>
                    </div>
                `;
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

        /* ========= LIST VIEW ========= */
        if (viewType.startsWith('list')) {
            const titleEl = info.el.querySelector('.fc-list-event-title');
            if (!titleEl) return;

            if (available === 0) {
                titleEl.innerHTML = `<strong>${info.event.title}</strong> - Fully booked`;
            } else if (available <= 2) {
                titleEl.innerHTML = `<strong>${info.event.title}</strong> - ⚠️ Only ${available}/5 Available`;
            } else {
                titleEl.innerHTML = `<strong>${info.event.title}</strong> - ${available}/5 Available`;
            }
        }
    },

    eventClick: function (info) {
        const available = info.event.extendedProps.available || 0;
        const disabled  = info.event.extendedProps.disabled || false;

        if (disabled || available === 0 || info.event.start < new Date()) {
            alert('This slot cannot be booked.');
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

        $('#mobile_error').removeClass('text-danger');
        $('#mobile').removeClass('input-error');

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
                    $('#mobile_error').addClass('text-danger');
                    $('#mobile').addClass('input-error');
                    $('#mobile').focus();
                    $('#mobile_error').html((response.message));
                    $('#confirmBooking').prop('disabled', false).text('Book');
                }
            })
            .fail(function (xhr) {
                const error = xhr.responseJSON;

                $('#mobile_error').addClass('text-danger');
                $('#mobile').addClass('input-error');
                $('#mobile').focus();
                $('#mobile_error').html((error?.message));
                $('#confirmBooking').prop('disabled', false).text('Book');
            });
    });
});

$(document).on('click', '.fc-event.available', function () {
    $('#mobile_error')
        .removeClass('text-danger')
        .text('');

    $('#mobile').removeClass('input-error');
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
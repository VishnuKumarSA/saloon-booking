let selected;
let modalInstance;

document.addEventListener('DOMContentLoaded', function () {

    let calendar = new FullCalendar.Calendar(
        document.getElementById('calendar'), {

        initialView: 'timeGridWeek',
        height: 'auto',
        expandRows: true,
        timeZone: 'local',

        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '01:00:00',
        allDaySlot: false,

        initialDate: new Date(),
        nowIndicator: true,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },

        datesSet: function (info) {

            const titleEl = document.getElementById('calendarTitle');
            if (!titleEl) return;

            switch (info.view.type) {

                case 'dayGridMonth':
                    titleEl.innerText = 'Monthly Booking';
                    titleEl.style.color = '#0d6efd';
                    break;

                case 'timeGridWeek':
                    titleEl.innerText = 'Weekly Booking';
                    titleEl.style.color = '#198754';
                    break;

                case 'timeGridDay':
                    titleEl.innerText = 'Daily Booking';
                    titleEl.style.color = '#1ab85eff';
                    break;

                case 'listWeek':
                    titleEl.innerText = 'Booking List';
                    titleEl.style.color = '#75db15ff';
                    break;

                default:
                    titleEl.innerText = 'Booking Calendar';
            }
        },

        dayCellDidMount: function (info) {

            if (info.view.type !== 'dayGridMonth') return;

            const bottom = info.el.querySelector('.fc-daygrid-day-bottom');
            if (!bottom) return;

            // Clear previous renders (important)
            bottom.innerHTML = '';

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const cellDate = new Date(info.date);
            cellDate.setHours(0, 0, 0, 0);

            // ❌ Hide EVERYTHING for past dates (including holidays)
            if (cellDate < today) return;

            const isHoliday = info.date.getDay() === 5; // Friday

            const summary = document.createElement('div');
            summary.className = 'month-summary';

            if (!isHoliday) {
                 summary.innerHTML = `<span class="available-text">9 Slots Available</span>`;
            } 
            bottom.appendChild(summary);
        },
        dateClick: function (info) {

            if (calendar.view.type === 'dayGridMonth') {

                if (info.date.getDay() === 5) {
                    alert('Holiday – booking not allowed');
                    return;
                }

                calendar.changeView('timeGridDay', info.date);
            }
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
            const disabled = info.event.extendedProps.disabled ?? false;
            const isHoliday = info.event.extendedProps.holiday ?? false;
            const viewType = info.view.type;

            /* ========= HOLIDAY LABEL ========= */
            /* ========= HOLIDAY ========= */
            if (isHoliday) {

                const viewType = info.view.type;

                // MONTH VIEW
                if (viewType === 'dayGridMonth') {

                    const timeEl = info.el.querySelector('.fc-event-time');
                    if (timeEl) {
                        timeEl.remove(); // removes "1p"
                    }

                    const dayCell = info.el.closest('.fc-daygrid-day-frame');
                    if (dayCell && !dayCell.querySelector('.holiday-label-month')) {

                        const label = document.createElement('div');
                        label.className = 'holiday-label-month';
                        label.innerText = 'Holiday';

                        dayCell.appendChild(label);
                    }

                    info.el.style.display = 'none'; // hide default event
                    return;
                }

                // WEEK / DAY VIEW
                if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {

                    const col = info.el.closest('.fc-timegrid-col');
                    if (col && !col.querySelector('.holiday-label')) {

                        const label = document.createElement('div');
                        label.className = 'holiday-label';
                        label.innerText = 'Holiday';

                        col.style.position = 'relative';
                        col.appendChild(label);
                    }

                    info.el.style.display = 'none';
                    return;
                }
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
                    titleEl.innerHTML = `<strong>${info.event.title}</strong>`;
                } else if (available <= 2) {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - ⚠️ Only ${available}/5 Available`;
                } else {
                    titleEl.innerHTML = `<strong>${info.event.title}</strong> - ${available}/5 Available`;
                }
            }
        },

        eventClick: function (info) {
            const available = info.event.extendedProps.available || 0;
            const disabled = info.event.extendedProps.disabled || false;

            if (disabled || available === 0 || info.event.start < new Date()) {
                alert('This slot cannot be booked.');
                return;
            }

            if (info.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridDay', info.event.start);
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
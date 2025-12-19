@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h4 class="mb-3">Weekly Booking</h4>
    <div id="calendar"></div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalLabel">Book Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="name" placeholder="Enter name" required>
                </div>
                <div class="mb-3">
                    <label for="mobile" class="form-label">Mobile Number</label>
                    <input type="tel" class="form-control" id="mobile" placeholder="Enter mobile number" required>
                    <div><span id = 'mobile_error'>  </span></div>
                </div>
                <div class="mb-3">
                    <label for="beautician" class="form-label">Select Beautician</label>
                    <select class="form-control" id="beautician" required>
                        <option value="">Select Beautician</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBooking">Book</button>
            </div>
        </div>
    </div>
</div>
@endsection
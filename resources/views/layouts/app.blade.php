<!DOCTYPE html>
<html>
<head>
<title>Salon Booking</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">


<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>




<style>
.fc-event{
  background:#eaffea!important;
  border:2px solid #28a745!important;
  color:#000!important;
  font-weight:600;
}
.fc-event:hover{
  background:#28a745!important;
  color:#fff!important;
}
.fc-day-fri{
  background:#fff5f5;
}
</style>
</head>
<body>
@yield('content')
</body>
</html>

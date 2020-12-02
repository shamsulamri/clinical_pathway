<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>
		{{ config('app.name', 'Laravel') }}
	</title>
<link rel="stylesheet" href="/css/bootstrap.min.css">
<link href="/fontawesome/css/all.css" rel="stylesheet"> <!--load all styles -->
<script type="text/javascript" src="/js/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="/js/moment.min.js"></script>
<script type="text/javascript" src="/js/tempusdominus-bootstrap-4.min.js"></script>
<script type="text/javascript" src="/js/jquery.mask.min.js"></script>

<link rel="stylesheet" href="/datetimepicker/tempusdominus-bootstrap-4.min.css" />

	<style>

		.bd-placeholder-img {
				font-size: 1.125rem;
				text-anchor: middle;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
		}

		@media (min-width: 768px) {
				.bd-placeholder-img-lg {
				  font-size: 3.5rem;
				}
		}

		.starter-template {
		  padding: 3rem 1.5rem;
		  text-align: center;
		}

		.bootstrap-datetimepicker-widget table {
			font-size: 90%;
		}

		.bootstrap-datetimepicker-widget td {
			border-top: 0px solid #dee2e6;
		}

		.bootstrap-datetimepicker-widget thead th {
			border-top: 0px solid #dee2e6;
			border-bottom: 0px solid #dee2e6;
		}

 /* The sidebar menu */
.sidenav {
  height: 100%; /* Full-height: remove this if you want "auto" height */
  width: 220px; /* Set the width of the sidebar */
  position: fixed; /* Fixed Sidebar (stay in place on scroll) */
  z-index: 1; /* Stay on top */
  top: 0; /* Stay at the top */
  left: 15px;
  overflow-x: hidden; /* Disable horizontal scroll */
  padding-top: 20px;
}

/* The navigation menu links */
.sidenav a {
  padding: 6px 8px 6px 16px;
  text-decoration: none;
  font-size: 16px;
  display: block;
}


/* Style page content */
.main {
  margin-left: 200px; /* Same as the width of the sidebar */
  padding: 0px 10px;
}

/* On smaller screens, where height is less than 450px, change the style of the sidebar (less padding and a smaller font size) */
@media screen and (max-height: 450px) {
  .sidenav {padding-top: 15px;}
  .sidenav a {font-size: 18px;}
}

	</style>
  </head>
<body>
		<main role="main" class="container">
			<br>
			@yield('content')
		</main>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
	<meta name="generator" content="Jekyll v3.8.5">
	<title>
		{{ config('app.name', 'Laravel') }}
	</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link href="/fontawesome/css/all.css" rel="stylesheet"> <!--load all styles -->
<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab&display=swap" rel="stylesheet">

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/js/tempusdominus-bootstrap-4.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js"></script>
<link rel="stylesheet" href="/datetimepicker/tempusdominus-bootstrap-4.min.css" />

	<style>
		body {
		   font-family: 'Roboto Slab' !important;
		}

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

		a {
		  color: gray;
		  text-decoration: none;
		}

		a:hover {
		  color: black;
		  text-decoration: none;
		}

		.jumbotron {
		  background-image: url("images/main.jpg");
		  background-size: cover;
		}
	</style>
  </head>
<body>
<div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 bg-light border-bottom shadow-sm">
	<div class="my-0 mr-md-auto font-weight-normal text-primary">
		<a href="/">
      <img src="/images/logo.png" width=50>
		<img src="/images/health2wealth.png" height=22>
		</a>
	</div>
@if (auth()->check()) 		
		<nav class="my-2 my-md-0 mr-md-3">
			<a class="p-2 text-dark" href="/home">HOME</a>
		</nav>
		@if (Gate::allows('edit-survey'))
				<div class="dropdown">
				  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Options
				  </button>
						  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class=dropdown-item href="/question_responses">Question_Responses</a>
								<a class=dropdown-item href="/projects">Projects</a>
								<a class=dropdown-item href="/question_sections">Sections</a>
								<a class=dropdown-item href="/question_groups">Groups</a>
								<a class=dropdown-item href="/questions">Questions</a>
								<a class=dropdown-item href="/users">Users</a>
								<a class=dropdown-item href="/survey_invitations">Invitations</a>
						  </div>
				</div>
				&nbsp;
				&nbsp;
		@endif
		<a class="btn btn-secondary" href="/logout">LOGOUT</a>
@else
		<nav class="my-2 my-md-0 mr-md-3">
			<a class="p-2 text-dark" href="/login">LOGIN</a>
		</nav>
		<a class="btn btn-outline-primary" href="/register">REGISTER</a>
@endif

</div>
		<main role="main">
			@yield('content')
		</main>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>

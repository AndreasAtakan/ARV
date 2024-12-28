<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

session_start();

require "init.php";
require_once "helper.php";

$loc = "accounts.php";
if(isset($_REQUEST['return_url'])) {
	$loc = $_REQUEST['return_url'];
}

if(isset($_REQUEST['auth'])) {
	$auth = $_REQUEST['auth'];
	header("location: auth.php?op=signin_authcode&auth=$auth"); exit;
}

if(signedIn()) { header("location: $loc"); exit; }

$signin_failed = $_GET['signin_failed'] ?? false; $signin_failed = $signin_failed == "true" ? true : false;
$signin_email = null;
if($signin_failed) { $signin_email = $_GET['email']; }

?>

<!DOCTYPE html>
<html lang="no">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="x-ua-compatible" content="ie=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<title>ARV – Arealregnskap med Visualisering</title>
	<meta name="title" content="ARV" />
	<meta name="description" content="Arealregnskap med Visualisering" />
	<meta name="author" content="Nordfjord EDB AS" />
	<meta name="copyright" content="Copyright(c) Nordfjord EDB AS. All rights reserved." />

	<link rel="apple-touch-icon" sizes="180x180" href="assets/icon_apple-touch-icon.png" />
	<link rel="icon" type="image/png" sizes="32x32" href="assets/icon_favicon-32x32.png" />
	<link rel="icon" type="image/png" sizes="16x16" href="assets/icon_favicon-16x16.png" />
	<link rel="manifest" href="assets/site.webmanifest" />

	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />

	<link rel="stylesheet" href="lib/fontawesome/css/all.min.css" />
	<!--script src="lib/fontawesome/js/all.min.js"></script-->

	<link rel="stylesheet" href="css/main.css" />

	<style type="text/css">

		main {
			height: calc(100vh - 56px);
			display: flex;
			align-items: center;
			justify-content: center;

			background-image: url('assets/background.png');
			background-size: cover;
			background-repeat: no-repeat;
			background-position: center;
		}
		main>form {
			max-width: 200px;
			width: 90%;
		}

		@media (max-width: 768px) {
			#md-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 992px) {
			#lg-margin-bottom { margin-bottom: 50px; }
		}

		#nav {
			background-color: #eba937 !important;
		}

	</style>
</head>
<body>

	<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="loadingModalLabel">Laster inn</h5>
					<!--button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button-->
				</div>
				<div class="modal-body">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Laster inn...</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<header>
		<nav class="navbar navbar-dark" id="nav">
			<div class="container">
				<a class="navbar-brand p-0" href="index.php">
					<img src="assets/logo.png" class="rounded" alt="ARV" height="40" width="auto" />
				</a>
			</div>
		</nav>
	</header>

	<main role="main">
		<form method="post" autocomplete="on" action="auth.php" name="login">
		<?php if($signin_failed) { ?>
			<div class="row g-0">
				<div class="col">
					<div role="alert" class="alert alert-danger">
						<strong>Feil innlogging</strong>. Vennligst prøv igjen.
					</div>
				</div>
			</div>
		<?php } ?>

			<input type="hidden" name="op" value="signin" />
			<input type="hidden" name="return_url" value="<?php echo $loc; ?>" />

			<div class="row g-0">
				<div class="col">
					<label for="email" class="form-label">E-post adresse</label>
					<input type="email" class="form-control mb-3" name="email" value="<?php if($signin_failed) { echo $signin_email; } ?>" />
				</div>
			</div>

			<div class="row g-0">
				<div class="col">
					<label for="password" class="form-label">Passord</label>
					<input type="password" class="form-control mb-4" name="password" />
				</div>
			</div>

			<div class="row g-0">
				<div class="col">
					<button type="submit" class="btn btn-info float-end">Logg inn</button>
				</div>
			</div>

			<div class="row g-0">
				<div class="col">
					<p class="mb-1 mt-3 text-shadow-light"><a class="text-dark" href="mailto:contact@geotales.io">Ta kontakt</a> med oss for å få innlogging.</p>
					<p class="mb-0 small text-shadow-light">© <span id="ccYear">2023</span>, Nordfjord EDB AS</p>
				</div>
			</div>
		</form>
	</main>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

	<script type="text/javascript" src="js/helper.js"></script>

	<script type="text/javascript">

		window.addEventListener("load", function() {
			$("span#ccYear").html( (new Date()).getFullYear() );

			$.ajax({
				type: "POST",
				url: "api.php",
				data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : null },
				success: function(result, status, xhr) { console.log("Analytics registered"); },
				error: function(xhr, status, error) { console.log(xhr.status, error); }
			});
		});

	</script>

</body>
</html>

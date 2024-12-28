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

$loc = "index.php";
if(isset($_REQUEST['return_url'])) {
	$loc = $_REQUEST['return_url'];
}

if(isset($_REQUEST['auth'])) {
	$auth = $_REQUEST['auth'];
	header("location: auth.php?op=signin_authcode&auth=$auth"); exit;
}

if(!signedIn()) {
	header("location: login.php?return_url=$loc"); exit;
}

$user_id = $_SESSION['user_id'];
$csrf_token = $_SESSION['csrf_token'];
$org_id = getUserOrganization($user_id);

$username = getUsername($user_id);
$email = getUserEmail($user_id);
$photo = getUserPhoto($user_id);

$stmt = $PDO->prepare("
	SELECT *
	FROM arv.\"Organization\"
	WHERE id = ?
");
$stmt->execute([$org_id]);
$row = $stmt->fetch();

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

		main {}

		#nav { background-color: #eba937 !important; }

		.ex {
			max-width: 450px;
			width: 100%;
			height: auto;
		}
		img.ex { border: 1px solid lightgrey; }

		@media (max-width: 768px) {
			#md-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 992px) {
			#lg-margin-bottom { margin-bottom: 50px; }
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
		<nav class="navbar navbar-expand-sm navbar-dark" id="nav">
			<div class="container">
				<a class="navbar-brand p-0" href="accounts.php">
					<img src="assets/logo.png" class="rounded" alt="ARV" height="40" width="auto" />
				</a>

				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarCollapse" style="flex-grow: 0;">
					<ul class="navbar-nav ms-auto">
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
								<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
							</a>
							<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
								<li><a class="dropdown-item" href="accounts.php">Mine arealregnskap</a></li>
								<li><a class="dropdown-item active" href="profile.php">Min profil</a></li>
								<li><a class="dropdown-item" href="about.php">Mer om ARV</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="logout.php?csrf_token=<?php echo $csrf_token; ?>">Logg ut</a></li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</nav>
	</header>

	<main role="main">
		<div class="container">
			<div class="row">
				<div class="col">
					<hr class="my-5" />

					<img class="rounded ms-3 mb-4" src="<?php echo $photo; ?>" alt="&nbsp;" height="80" width="auto" />

					<h2 class="text-muted ms-5 mt-2" style="display: inline-block;">
						<?php echo $username; ?>
					</h2>

					<p class="mt-4">
						E-post:
							<a class="text-dark" href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a>
					</p>

					<p>
						Min organisasjon:
							<strong><?php echo $row['name']; ?></strong> –
							org.nr. <a class="text-dark" href="https://data.brreg.no/enhetsregisteret/oppslag/enheter/<?php echo $row['org.nr.']; ?>" target="_blank"><?php echo $row['org.nr.']; ?></a>
					</p>

					<hr class="my-5" />
				</div>
			</div>
		</div>
	</main>

	<footer class="container">
		<p>© <span id="ccYear">2023</span>, Nordfjord EDB AS</p>
	</footer>

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

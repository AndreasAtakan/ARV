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

$signed_in = signedIn();
$user_id = null;
$csrf_token = null;
$photo = null;

if($signed_in) {
	$user_id = $_SESSION['user_id'];
	$csrf_token = $_SESSION['csrf_token'];
	$photo = getUserPhoto($user_id);
}

?>

<!DOCTYPE html>
<html lang="no">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="x-ua-compatible" content="ie=edge" />
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, shrink-to-fit=no, user-scalable=no, target-densitydpi=medium-dpi" />
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

		:root {
			--header-height: 56px;
			--banner-height: 300px;
		}

		main {}

		#nav { background-color: #eba937 !important; }

		.link-dark { color: black; }

		#banner {
			position: absolute;
			z-index: -1;
			top: var(--header-height);
			left: 0;
			width: 100%;
			height: var(--banner-height);

			background-image: url('assets/banner1_blur.jpg');
			background-position: top;
			background-repeat: no-repeat;
			background-size: cover;
		}

		@media (max-width: 992px) {
			#lg-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 768px) {
			#md-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 576px) {
			/**/
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
		<nav class="navbar navbar-expand-sm navbar-dark shadow" id="nav">
			<div class="container">
				<a class="navbar-brand p-0" href="<?php echo signedIn() ? "accounts.php" : "index.php"; ?>">
					<img src="assets/logo.png" class="rounded" alt="ARV" height="40" width="auto" />
				</a>

				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarCollapse" style="/*flex-grow: 0;*/">
					<ul class="navbar-nav me-auto my-2 my-sm-0 navbar-nav-scroll" style="--bs-scroll-height: 250px;">
						<li class="nav-item me-sm-2">
							<a class="nav-link active" aria-current="page" href="index.php">Hjem</a>
						</li>
						<li class="nav-item me-sm-2">
							<a class="nav-link" href="about.php">Mer om ARV</a>
						</li>
						<li class="nav-item me-sm-2">
							<a class="nav-link" href="about.php#contact">Kontakt oss</a>
						</li>
					</ul>

					<?php if($signed_in) { ?>
						<div class="dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
								<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
							</a>
							<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
								<li><a class="dropdown-item" href="accounts.php">Mine arealregnskap</a></li>
								<li><a class="dropdown-item" href="profile.php">Min profil</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="logout.php?csrf_token=<?php echo $csrf_token; ?>">Logg ut</a></li>
							</ul>
						</div>
					<?php } else { ?>
						<a role="button" class="btn btn-sm btn-outline-light my-1" href="login.php">Logg inn</a>
					<?php } ?>
				</div>
			</div>
		</nav>
	</header>

	<main role="main">
		<div class="container">
			<div class="row mb-5">
				<div class="col">
					<div class="m-0 p-0 shadow" id="banner"></div>

					<div style="margin: 3rem auto;"></div>

					<h2 class="text-light text-shadow" style="margin: 12rem auto 6rem auto;">
						<strong>
							ARV – Arealregnskap med Visualisering
						</strong>
					</h2>

					<p class="lead mb-3">
						<u>ARV er et komplett system for å produsere, behandle og fremvise <a href="https://www.regjeringen.no/no/tema/plan-bygg-og-eiendom/plan_bygningsloven/planlegging/plansystem_prosess/kunnskapsgrunnlaget_plan/arealregnskap_kommuneplan/id2913557/?expand=factbox2913582" target="_blank"><em>arealregnskap</em></a>.</u> <br />
						I ARV får du tilgang til et interaktivt kart over arealregnskapet, et dashbord-område med grafer og statistikk, og omfattende rapporter av resultatene. <br />
						<em style="font-size: 16px;">Trykk 'Logg inn' for å lage dine arealregnskap.</em>
					</p>

					<p class="lead mb-1">
						ARV produserer arealregnskap basert på kommuners planer eller utbyggeres private planer, <br />
						og oppfyller <a href="https://www.regjeringen.no/no/dokumenter/arealregnskap-i-kommuneplan/id3017913/" target="_blank">KDDs veileder</a> for arealregnskap i kommuner.
					</p>
				</div>
			</div>

			<div class="row mb-5">
				<div class="col-md">
					<p class="mb-1" style="font-size: 24px;">
						<strong>Hva er et arealregnskap?</strong>
					</p>

					<p class="lead mb-1">
						Et arealregnskap gir oversikt over planlagte endringer i arealdelen innenfor en kommune, og det kan vise hvilket utbyggingspotensial som ligger inne i eksisterende planer. <br />
						Arealregnskapet viser også hvilken arealtyper som ligger i utbyggingspotensial og eventuelle konflikter mellom utbyggingspotensialet og spesielt viktig områder.
					</p>
				</div>

				<div class="col-md">
					<p class="mb-1">
						<strong>Utforsk kart over landsdekkende planlagte utbyggingsområder i kommunenes planer</strong>.
					</p>
					<p class="small">
						<em>Metoden for å identifisere planlagte utbyggingsområder er basert på <a href="https://brage.nina.no/nina-xmlui/handle/11250/3085779" target="_blank">forskningsrapport fra NINA</a></em>.
					</p>

					<a role="button" class="btn btn-lg btn-info float-end shadow my-3" href="map.html">Utforsk kart</a>
				</div>
			</div>

			<div class="row">
				<div class="col">
					<p class="mb-1" style="font-size: 20px;">
						<strong>Ta kontakt med oss</strong>
					</p>

					<p class="lead mb-1">
						Har du flere spørsmål eller ønsker du arealregnskap for din organisasjon? <br />
						Ta kontakt med oss her: <br />

						<a href="mailto:contact@geotales.io" class="text-dark">contact@geotales.io</a> ,
						+47 468 45 196
					</p>

					&nbsp;

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

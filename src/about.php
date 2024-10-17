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
			max-width: 700px;
			width: 100%;
			height: auto;
		}
		img.ex { border: 1px solid lightgrey; }

		@media (max-width: 992px) {
			#lg-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 768px) {
			#md-margin-bottom { margin-bottom: 50px; }
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
				<a class="navbar-brand p-0" href="<?php echo signedIn() ? "accounts.php" : "index.php"; ?>">
					<img src="assets/logo.png" class="rounded" alt="ARV" height="40" width="auto" />
				</a>

				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarCollapse" style="/*flex-grow: 0;*/">
					<ul class="navbar-nav me-auto">
						<li class="nav-item me-sm-2">
							<a class="nav-link" href="index.php">Hjem</a>
						</li>
						<li class="nav-item me-sm-2">
							<a class="nav-link active" aria-current="page" href="about.php">Mer om ARV</a>
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
			<div class="row">
				<div class="col">
					<hr class="my-5" />

					<h2 class="text-muted mt-3 mb-4">
						ARV – Arealregnskap med Visualisering
					</h2>

					<p class="lead mb-2">
						<u>ARV er en interaktiv kartløsning for å gi oversikt over prosjekters <a href="https://www.regjeringen.no/no/tema/plan-bygg-og-eiendom/plan_bygningsloven/planlegging/plansystem_prosess/kunnskapsgrunnlaget_plan/arealregnskap_kommuneplan/id2913557/?expand=factbox2913582" target="_blank"><em>arealregnskap</em></a>.</u>
						<!-- ARV er et system for å produsere fullstendige <a class="link-dark" href="https://www.regjeringen.no/no/tema/plan-bygg-og-eiendom/plan_bygningsloven/planlegging/plansystem_prosess/kunnskapsgrunnlaget_plan/arealregnskap_kommuneplan/id2913557/?expand=factbox2913582" target="_blank">arealregnskap</a> for kommuner eller byggeprosjekter</a>. -->
					</p>

					<p class="lead mb-2">
						ARV produserer arealregnskap basert på kommuners planer eller utbyggeres private planer, <br />
						og oppfyller <a href="https://www.regjeringen.no/no/dokumenter/arealregnskap-i-kommuneplan/id3017913/" target="_blank">KDDs veileder</a> for arealregnskap i kommuner.
					</p>

					<p class="lead mb-1">
						I arealregnskapet inngår:
						<ul>
							<li>Kart som viser planlagte utbyggingsområder og områdenes overlapp med forskellige viktige arealer <a class="text-dark" href="#ex1">#</a></li>
							<li>Rapport som viser oppsummert statistikk over utbyggingsområdene (i form av PDF eller Word-fil) <a class="text-dark" href="#ex2">#</a></li>
							<li>Dokumentasjon av regnskapets beregninger <a class="text-dark" href="#ex3">#</a></li>
							<li>Mulighet for integrasjon mot andre GIS-løsninger</li>
						</ul>
					</p>

					<p class="lead mt-4 mb-2">
						<strong>Eksempler fra et arealregnskap:</strong>
					</p>

					<video class="rounded mb-3 ex" id="ex1" controls>
						<source type="video/mp4" src="assets/ex1.mp4" />
						[Video not supported]
					</video>
					<br />

					<a class="text-dark" id="ex2" href="Rapport.pdf#page=2" target="_blank">
						<img class="rounded mb-3 ex" src="assets/ex2.png" alt="[Not found]" />
					</a>
					<br />

					<img class="rounded mb-3 p-1 ex" id="ex3" src="assets/ex3.png" alt="[Not found]" />
					<br />

					<!-- Tegneredskap hvor man kan markere omåder av interesse og se naturverdier i dette området -->

					<p class="lead mt-4 mb-0">Metoden for å identifisere planlagte utbyggingsområder i en kommunes planer er basert på forskningsrapport fra NINA (Norsk Institutt for Naturforskning).</p>
					<p class="mb-4">
						Kort fortalt identifiserer vi planreserver / tomtereserver i en kommunes planer ved å bygge opp en planmosaikk av kommuneplan/-delplaner og reguleringsplaner, og korrigerer denne med data for allerede utbygget areal. <br />
						Deretter analyserer vi disse reservene opp mot ulike interesseområder og kategorier, f.eks. myr, skog, jordbruk, kulturlandskap, skred- og flomsoner, planenes alder / arealformål, o.l. <br />
						Ulike kommuner har ulike behov, og vi kan tilpasse analysen i alle ledd. <br />
						<strong>Les rapporten <a class="link-dark" href="Dokumentasjon.pdf" target="_blank">her</a></strong>. <br />
						(For planlagt utbygging til samferdsel, idrettsanlegg og «andre formål», inkludert energianlegg, vurderer vi resultatene som for usikre til å anvende resultatene som anslag for planlagt utbygd areal.
						 Områdene kan likevel enkelt inkluderes ved behov.)
					</p>

					&nbsp;

					<h4 class="mt-5 mb-3" id="contact">
						For mer informasjon, ta kontakt
					</h4>

					<div class="row g-0 mb-5">
						<div class="col-12 col-md">
							<p class="lead">
								Jan Eirik Heggdal <br />
								<a href="mailto:jeh@geotales.io" class="text-dark">jeh@geotales.io</a> ,
								+47 468 45 196
							</p>
						</div>

						<div class="col-12 col-md">
							<p class="lead">
								Andreas Atakan <br />
								<a href="mailto:aca@geotales.io" class="text-dark">aca@geotales.io</a> ,
								+47 480 06 325
							</p>
						</div>
					</div>

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

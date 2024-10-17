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

if(!signedIn()) {
	header("location: login.php?return_url=$loc"); exit;
}

$user_id = $_SESSION['user_id'];
$csrf_token = $_SESSION['csrf_token'];
$org_id = getUserOrganization($user_id);
$photo = getUserPhoto($user_id);

$org = getOrganization($org_id); $stmt = null;
if($org["name"] == "__all__") {
	$stmt = $PDO->prepare("
		SELECT *
		FROM \"Accounts\"
		ORDER BY created_date DESC
	");
	$stmt->execute();
}
else {
	$stmt = $PDO->prepare("
		SELECT *
		FROM \"Accounts\"
		WHERE organization_id = ?
		ORDER BY created_date DESC
	");
	$stmt->execute([$org_id]);
}

$rows = $stmt->fetchAll();
$count = $stmt->rowCount();

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

	<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
	<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" />

	<!--link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" /-->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />

	<script src="https://code.highcharts.com/highcharts.js"></script>
	<script src="https://code.highcharts.com/modules/data.js"></script>
	<script src="https://code.highcharts.com/modules/drilldown.js"></script>
	<script src="https://code.highcharts.com/modules/sunburst.js"></script>
	<script src="https://code.highcharts.com/modules/exporting.js"></script>
	<script src="https://code.highcharts.com/modules/export-data.js"></script>
	<script src="https://code.highcharts.com/modules/offline-exporting.js"></script>
	<script src="https://code.highcharts.com/modules/accessibility.js"></script>
	<script src="https://code.highcharts.com/modules/no-data-to-display.js"></script>

	<link rel="stylesheet" href="lib/fontawesome/css/all.min.css" />
	<!--script src="lib/fontawesome/js/all.min.js"></script-->

	<link rel="stylesheet" href="css/main.css" />

	<style type="text/css">

		:root { --header-height: 58.22px; }

		main {
			height: calc(100vh - var(--header-height));
			background-color: white;
		}

		#nav { background-color: #eba937 !important; }

		#mapCont, #section { height: inherit; }
		#mapCont {
			position: absolute;
			right: 0;
			width: 76%;
		}
		#section {
			position: absolute;
			left: 0;
			width: 24%;
			min-width: 200px;
			max-width: 50%;
			resize: horizontal;
			overflow-y: auto;
		}

		.modal-content #upload>.col,
		.modal-content #upload>div[class^='col-'] { border: 1px dashed black; }

		.color-preview {
			display: inline-block;
			border: 1px solid lightgrey;
			border-radius: 2px;
			width: 15px;
			height: 15px;
		}
		.color-preview#overlapp { background-color: red; }
		.color-preview#planlagt_01 { background-color: #E6E600; }
		.color-preview#planlagt_02 { background-color: #FFCC33; }
		.color-preview#planlagt_03 { background-color: #FF6699; }
		.color-preview#planlagt_04 { background-color: #9999FF; }
		.color-preview#planlagt_05 { background-color: #666699; }
		.color-preview#planlagt_06 { background-color: #9966CC; }
		.color-preview#planlagt_07 { background-color: #AC6668; }
		.color-preview#planlagt_08 { background-color: #996600; }
		.color-preview#planlagt_09 { background-color: #669900; }
		.color-preview#planlagt_10 { background-color: #CCFFFF; }
		.color-preview#planlagt_11 { background-color: #999999; }
		.color-preview#planlagt_12 { background-color: #00CC99; }
		.color-preview#planlagt_13 { background-color: #999966; }
		.color-preview#planlagt_14 { background-color: #CC6600; }
		.color-preview#planlagt_15 { background-color: #B35900; }
		.color-preview#planlagt_16 { background-color: #66B1B1; }
		.color-preview#planlagt_17 { background-color: #0066CC; }
		.color-preview#planlagt_18 { background-color: #3333CC; }
		.color-preview#planlagt_19 { background-color: #4d4d4d; }

		.formaal_inkludert { color: darkred; }

		.mapboxgl-ctrl {
			-webkit-transition: opacity 0.4s;
			-moz-transition: opacity 0.4s;
			-ms-transition: opacity 0.4s;
			-o-transition: opacity 0.4s;
			transition: opacity 0.4s;
		}
		.custom-map-control {
			margin-bottom: 4px !important;
		}
		.mapboxgl-ctrl-bottom-center {
			position: absolute;
			bottom: 0;
			left: 50%;
			transform: translateX(-50%);
			z-index: 2;
			pointer-events: none;
		}
		.mapboxgl-ctrl-bottom-center .mapboxgl-ctrl {
			margin-bottom: 10px;
		}
		.mapboxgl-ctrl-geocoder {
			min-width: auto !important;
			max-width: 350px !important;
			width: calc(100vw - 39px - 20px) !important;
		}
		.mapboxgl-ctrl-geocoder .mapboxgl-ctrl-geocoder--icon-search,
		.mapboxgl-ctrl-geocoder .mapboxgl-ctrl-geocoder--button { 
			top: 12px !important;
		}
		.mapboxgl-ctrl-geocoder .mapboxgl-ctrl-geocoder--input {
			height: 45px !important;
		}
		.mapboxgl-popup .mapboxgl-popup-content {
			max-width: 350px !important;
			max-height: 280px !important;
			overflow: auto;
			padding: 15px 20px;
		}
		/*.mapboxgl-popup .mapboxgl-popup-close-button {}*/
		.mapboxgl-popup #obj_report_add {
			position: fixed;
			top: 4px;
			left: 4px;
		}
		.mapboxgl-popup #obj_report_add:hover { cursor: pointer; }

		#map { min-height: 150px; }

		.highcharts-menu hr { margin: 2px 0; }
		.highcharts-contextmenu { top: -24px !important; }
		table.dataTable { margin-top: 0 !important; }

		#report { overflow-y: auto; }
		#report_tab button { padding: 0.1rem 0.5rem; }
		#report_tab_content>.active {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			justify-content: flex-start;
			align-items: flex-start;
			row-gap: 60px;
		}
		#report #dashboard .graph {
			/*overflow: visible !important;*/
			width: calc(50% - 10px);
			height: 300px;
			border: 1px solid lightgrey;
			margin: auto 5px;
		}
		#report #dashboard .graph.sub {
			width: calc(33.3% - 10px);
			height: 200px;
		}
		#report #dashboard .graph#add {
			width: 150px;
			height: 100px;
		}
		#report #dashboard #choose { overflow-y: auto; }
		#report #dashboard #choose label { word-break: break-all; }
		#report .active#balance { display: block; }
		/* #report #table #plan_table */
		#report #table #plan_table tbody tr:hover { cursor: pointer; }

		#reportModal #report_obj_table tr #remove:hover { cursor: pointer; }

		@media (max-width: 992px) {
			#lg-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 768px) {
			#md-margin-bottom { margin-bottom: 50px; }
		}
		@media (max-width: 576px) {
			#mapCont, #section {
				width: 100% !important;
				max-width: none;
			}
			#mapCont { height: 50%; }
			#section {
				top: calc(50% + var(--header-height));
				height: calc(50% - var(--header-height));
				resize: none;
			}
		}

	</style>
</head>
<body>

	<div class="modal fade" id="newModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="newModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="newModalLabel">Lag nytt regnskap</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">
					<form enctype="multipart/form-data" id="accounts">
						<input type="hidden" name="op" value="accounts_create" />
						<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>" />

						<div class="row">
							<div class="col mb-3">
								<p class="lead">
									ARV vil identifisere arealreserven og arealformålsendringer i planene, og analysere disse opp mot utvalgte interesseområder. <br />
									For å gjøre dette vil de oppgitte planene sammenstilles og korrigeres i forhold til allerede utbygde områder. <br />
									For mer informasjon om metoden, se <a class="text-dark" href="#" data-bs-toggle="modal" data-bs-target="#docModal">her</a>.
								</p>
							</div>
						</div>

						<div class="row">
							<div class="col mb-3">
								<label for="title" class="form-label">Tittel på regnskapet</label>
								<input type="text" class="form-control form-control-sm" id="title" name="title" maxlength="250" required />
							</div>
						</div>

						<div class="row">
							<div class="col">
								<p class="mb-0">Last opp planene som skal inngå i regnskapet.</p>
								<p>Aksepterte filformater er: • SOSI (.sos) • GML (.gml) • Shape (.zip mappe) • GeoPackage (.gpkg)</p>
								<p>Last opp en eller flere planer som skal vurderes:</p>
							</div>
						</div>

						<div class="row align-items-center" id="upload">
							<div class="col py-5 my-3">
								<input type="file" class="form-control form-control-sm" id="plan" name="plan[]" accept=".sos, application/gml+xml, application/zip, application/geopackage+sqlite3, .gpkg" multiple />
								<br />
								<!--div class="form-check">
									<input type="checkbox" class="form-check-input" value="checked" id="regplanPriValg" name="regplanPriValg" />
									<label class="form-check-label small" for="regplanPriValg">
										Reguleringsplan velges framfor kommuneplan/-delplan hvor disse kommer i konflikt
										&nbsp;
										<i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Huk av dette valget dersom du ønsker at de oppgitte reguleringsplanene skal prioriteres framfor de andre planene. <br /> Dette er relevant dersom reguleringsplanene har nyere vedtaksdato enn de andre planene."></i>
									</label>
								</div-->
								<span class="text-muted small d-none" id="regpri_help">
									Huk av på reguleringsplanene dersom du ønsker de skal prioriteres framfor kommuneplan/-delplan hvor disse kommer i konflikt.
									&nbsp;
									<i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Dette er relevant dersom reguleringsplanene har nyere vedtaksdato enn de andre planene."></i>
								</span>
								<br />
								<ul class="list-group list-group-flush" id="files"></ul>
							</div>
						</div>

						<div class="row">
							<div class="col my-3">
								<label for="plan_datoHelp" class="form-label">
									Velg dato for gjeldende plan
									&nbsp;
									<i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Denne datoen vil brukes til å skille mellom tidligere planer og nye planer."></i>
								</label>
								<input type="date" class="form-control form-control-sm" id="plan_dato" name="plan_dato" aria-describedby="plan_datoHelp" style="width: 200px;" required />
							</div>
						</div>

						<div class="row">
							<div class="col my-3">
								<label for="description" class="form-label small">Legg til et notat</label>
								<textarea class="form-control form-control-sm" id="description" name="description" rows="4"></textarea>
								<!--div class="accordion" id="accordionPlan">
									<div class="accordion-item">
										<h2 class="accordion-header" id="headingPlan">
											<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlan" aria-expanded="false" aria-controls="collapsePlan">
												<i class="fa-solid fa-gear"></i>
												&nbsp;
												Avanserte valg
											</button>
										</h2>
										<div class="accordion-collapse collapse" id="collapsePlan" aria-labelledby="headingPlan" data-bs-parent="#accordionPlan">
											<div class="accordion-body">
												<div class="row">
													<div class="col-md">
														<p class="my-3">
															<strong>Data for gjeldende kommuneplan hentes fra <em>GeoNorge</em></strong>.
														</p>
													</div>
												</div>

												<div class="row">
													<div class="col my-3">
														<label for="description" class="form-label small">Legg til et notat</label>
														<textarea class="form-control form-control-sm" id="description" name="description" rows="4"></textarea>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div-->
							</div>
						</div>
					</form>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="create">Kjør</button>
				</div>
			</div>
		</div>
	</div>

	<header>
		<nav class="navbar navbar-expand-sm navbar-dark shadow" id="nav">
			<div class="container-fluid">
				<a class="navbar-brand p-0" href="#">
					<img src="assets/logo.png" class="rounded" alt="ARV" height="40" width="auto" />
				</a>

				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarCollapse">
					<ul class="navbar-nav w-100 my-2 my-sm-0 navbar-nav-scroll" style="--bs-scroll-height: 250px;">
						<li class="nav-item me-sm-4">
							<button type="button" class="btn btn-sm btn-outline-light px-4 py-2" data-bs-toggle="modal" data-bs-target="#newModal">
								<strong>Lag nytt regnskap</strong>
							</button>
						</li>
						<li class="nav-item mt-2 me-sm-auto">
							<span class="d-none" style="color: #fdf3e8;">Viser <strong id="planlegging_status_view">tidligere</strong> plan</span>
						</li>
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
								<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
							</a>
							<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
								<li><a class="dropdown-item active" href="#">Mine arealregnskap</a></li>
								<li><a class="dropdown-item" href="profile.php">Min profil</a></li>
								<li><a class="dropdown-item" href="about.php">Mer om ARV</a></li>
								<li><hr class="dropdown-divider" /></li>
								<li><a class="dropdown-item" href="logout.php?csrf_token=<?php echo $csrf_token; ?>">Logg ut</a></li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</nav>
	</header>

	<main role="main" class="p-0">
		<!-- MODALS ARE PLACED HERE TO ENSURE THAT THEY ARE SHOWN WHEN MAP-FULLSCREEN IS PRESSED (must be inside the container element that is made fullscreen by the plugin) -->
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

		<div class="modal fade" id="errorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="errorModalLabel" style="color: #b30000;">
							<strong>Feil</strong>
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p>Noe gikk galt. Vennligst prøv på nytt.</p>
						<p class="text-muted small">
							Kode: <span id="errorCode"></span> <br />
							<a class="text-decoration-none" id="errorSend" href="mailto:contact@geotales.io?subject=ARV feilmelding&body=(Legg ved et skjermbilde av feilmeldingen)">Send feilmelding</a>
						</p>
					</div>
					<!--div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Lukk</button>
					</div-->
				</div>
			</div>
		</div>

		<div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="editModalLabel">Rediger regnskaps-info</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col mb-3">
									<p class="lead">
										Her kan du redigere vedlagt informasjon om regnskapet. <br />
										Fyll inn ny ønsket informasjon og trykk "Lagre".
									</p>
								</div>
							</div>

							<div class="row">
								<div class="col mb-3">
									<label for="title" class="form-label">Tittel på regnskapet</label>
									<input type="text" class="form-control form-control-sm" id="title" name="title" maxlength="250" required />
								</div>
							</div>

							<div class="row">
								<div class="col mb-3">
									<label for="description" class="form-label small">Notat</label>
									<textarea class="form-control form-control-sm" id="description" name="description" rows="4"></textarea>
								</div>
							</div>

							<hr class="my-5" />

							<div class="row">
								<div class="col mb-3">
									<h4 class="text-red">Slett regnskapet</h4>
									<p class="mb-4" style="font-size: 18px;"><strong class="text-red">Fare!</strong> Å slette regnskapet kan ikke reverseres.</p>

									<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Slett</button>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Avbryt</button>
						<button type="button" class="btn btn-secondary" id="save">Lagre</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title text-red" id="deleteModalLabel">Slett regnskap</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p><strong class="text-red">Fare!</strong> Er du sikker på at du vil slette regnskapet? Dette kan ikke reverseres.</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Avbryt</button>
						<button type="button" class="btn btn-danger" id="delete">Slett</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="docModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="docModalLabel">Dokumentasjon av regnskapet</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<h4 class="text-muted">Tittel: <span id="title">–</span></h4>
						<p class="text-muted mb-0">Regnskap produsert: <span id="created_date">–</span></p>
						<p class="small mb-3">Notat: <span id="description">–</span></p>

						<p class="text-muted small">
							Metoden for å produsere arealregnskap i ARV er basert på veileder fra KDD. <br />
							For en full beskrivelse av metoden, samt forventningene til arealregnskap, les veilederen <a class="text-dark" href="https://www.regjeringen.no/no/dokumenter/arealregnskap-i-kommuneplan/id3017913/" target="_blank"><strong>her</strong></a>.
						</p>

						<hr class="my-4" />

						<h4 class="text-muted">Dokumentasjon av analysen</h4>
						<p class="lead mb-0">ARV produserer arealregnskap ved å identifisere arealreserven og arealformålsendringer i planene, og analysere disse opp mot valgte interesseområder.</p>
						<p class="mb-0">
							For å gjøre dette vil de oppgitte planene først <em class="text-purple">klargjøres og sammenstilles</em>, så vil <em class="text-purple">arealformålsendringene beregnes</em>, så korrigeres i forhold til <em class="text-purple">allerede utbygde områder</em> og til slutt vil <em class="text-purple">potensielle arealkonflikter identifiseres</em>. <br />
							Arealregnskapet blir til slutt framstilt i kartet og dashbordet på hovedsiden.
						</p>
						<p class="mb-4">
							<em>Under vises et detaljert diagram av denne analysen:</em>
						</p>
						<a href="assets/analyse-dokumentasjon.png" target="_blank">
							<img class="mb-4" alt="[Fant ikke diagram]" src="assets/analyse-dokumentasjon.png" width="100%" height="auto" />
						</a>

						<hr class="my-5" />

						<h4 class="text-muted">Dokumentasjon av datasett</h4>
						<p class="mb-4">
							Dette er en oversikt over hvert datasaett som inngår i regnskapet:
						</p>
						<table class="table caption-top text-center align-middle mb-4">
							<!--caption>Data som inngår i regnskapet</caption-->
							<thead>
								<tr>
									<th scope="col" class="col-3">Datasett</th>
									<th scope="col" class="col-3">Dataeier</th>
									<th scope="col" class="col-3">Uthentingsdato</th>
									<th scope="col" class="col-3">Steg i metoden</th>
								</tr>
							</thead>
							<tbody class="table-group-divider">
								<!--tr>
									<td scope="row">Kommuneplan</td>
									<td>Kommunen</td>
									<td id="plandata_date">-</td>
									<td>
										1. Klargjøre og sammenstille, <br />
										2. Beregne arealformålsendringer
									</td>
								</tr>
								<tr>
									<td scope="row">Kommunedelplan</td>
									<td>Kommunen</td>
									<td id="plandata_date">-</td>
									<td>
										1. Klargjøre og sammenstille, <br />
										2. Beregne arealformålsendringer
									</td>
								</tr>
								<tr>
									<td scope="row">
										Reguleringsplan <br />
										vertikalnivå 2
									</td>
									<td>Kommunen</td>
									<td id="plandata_date">-</td>
									<td>
										1. Klargjøre og sammenstille, <br />
										2. Beregne arealformålsendringer
									</td>
								</tr-->

								<tr id="planfiler_divider">
									<td scope="row"></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>

								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/ssb-arealbruk-2023/a965a979-c12a-4b26-90a0-f09de47dbecd" target="_blank">SSB-arealbruk 2023</a>
									</td>
									<td>Statistisk sentralbyrå (SSB)</td>
									<td>juli 2023</td>
									<td>
										3. Fjerne allerede utbygde områder
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/fkb-bygning/8b4304ea-4fb0-479c-a24d-fa225e2c6e97" target="_blank">FKB-Bygning</a>
									</td>
									<td>Kartverket</td>
									<td>februar 2023</td>
									<td>
										3. Fjerne allerede utbygde områder
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/fkb-tiltak/8944603c-9414-43a7-9421-9a1de9850a96" target="_blank">FKB-Tiltak</a>
									</td>
									<td>Kartverket</td>
									<td>februar 2023</td>
									<td>
										3. Fjerne allerede utbygde områder
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/fkb-veg/4920b452-75cc-45f2-964c-3378204c3517" target="_blank">FKB-Veg</a>
									</td>
									<td>Kartverket</td>
									<td>februar 2023</td>
									<td>
										3. Fjerne allerede utbygde områder
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/fkb-ar5/166382b4-82d6-4ea9-a68e-6fd0c87bf788" target="_blank">FKB-AR5</a>
									</td>
									<td>Kartverket</td>
									<td>juli 2023</td>
									<td>
										3. Fjerne allerede utbygde områder, <br />
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>

								<tr>
									<td scope="row"></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>

								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/kulturlandskap-verdifulle/a6368bed-4896-41d3-92aa-cc2b4261adc3" target="_blank">Verdifulle kulturlandskap</a>
									</td>
									<td>Miljødirektoratet</td>
									<td>september 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/skredfaresoner/b2d5aaf8-79ac-40f3-9cd6-fdc30bc42ea1" target="_blank">Skredfaresoner</a>
									</td>
									<td>Norges vassdrags- og energidirektorat (NVE)</td>
									<td>juli 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/flomsoner/e95008fc-0945-4d66-8bc9-e50ab3f50401" target="_blank">Flomsoner</a>
									</td>
									<td>Norges vassdrags- og energidirektorat (NVE)</td>
									<td>juli 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/naturtyper-i-norge-landskap/77512fbd-cfc5-497a-8c41-ebaf5f736ded" target="_blank">Områder over skoggrensen</a>
									</td>
									<td>
										Bryn 2013, Simensen m.fl. 2021 <br />
										ved NINA
									</td>
									<td>juli 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/potensielt-tilgjengelig-strandsone/8aa793a8-272e-4756-92f6-0213b6b6ba2c" target="_blank">Strandsoneområder</a>
									</td>
									<td>Statistisk sentralbyrå (SSB)</td>
									<td>juli 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://kartkatalog.geonorge.no/metadata/villreinomraader/fc59e9a4-59df-4eb3-978a-1c173b84bf4e" target="_blank">Villreinområder</a>
									</td>
									<td>Miljødirektoratet</td>
									<td>juli 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
								<tr>
									<td scope="row">
										<a class="text-dark" href="https://www.birdlife.no/prosjekter/iba/" target="_blank">Viktige fugleområder</a>
									</td>
									<td>Birdlife.no</td>
									<td>september 2023</td>
									<td>
										4. Identifisere potensielle arealkonflikter
									</td>
								</tr>
							</tbody>
						</table>

						<hr class="my-5" />

						<h4 class="text-muted">Definisjon av arealformålsgrupper</h4>
						<p class="mb-4">
							Dette er en oversikt over grupperingene av arealformålskodene i plandataene, samt hvilken arealformål som inkluderes i regnskapet og ikke. <br />
							Arealformålene som er inkludert i regnskapet er markert med rødt:
						</p>
						<details class="mb-1">
							<summary>01 Bolig eller sentrumsformål</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1001 Bebyggelse og anlegg</li>
								<li class="formaal_inkludert">1110 Boligbebyggelse</li>
								<li class="formaal_inkludert">1130 Sentrumsformål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>02 Fritidsbebyggelse</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1120 Fritidsbebyggelse</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>03 Tjenesteyting</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1160 Offentlig eller privat tjenesteyting</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>04 Handel</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1140 Kjøpesenter</li>
								<li class="formaal_inkludert">1150 Forretninger</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>05 Turistformål</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1170 Fritids- og turistformål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>06 Næringsvirksomhet</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1300 Næringsvirksomhet</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>07 Råstoffutvinning</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1200 Råstoffutvinning</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>08 Kombinerte formål</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">1800 Kombinert bebyggelse og anleggsformål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>09 Idrettsanlegg</summary>
							<ul class="m-0">
								<li>1400 Idrettsanlegg</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>10 Andre formål</summary>
							<ul class="m-0">
								<li>1500 Andre typer nærmere angitt bebyggelse og anlegg</li>
								<li>1600 Uteoppholdsareal</li>
								<li>1700 Grav-og urnelund</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>11 Samferdselsanlegg</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">2001 Samferdselsanlegg og teknisk infrastruktur (arealer)</li>
								<li class="formaal_inkludert">2010 Veg</li>
								<li class="formaal_inkludert">2020 Bane</li>
								<li class="formaal_inkludert">2030 Lufthavn</li>
								<li class="formaal_inkludert">2050 Hovednett for sykkel</li>
								<li class="formaal_inkludert">2060 Kollektivnett</li>
								<li class="formaal_inkludert">2070 Kollektivknutepunkt</li>
								<li class="formaal_inkludert">2080 Parkeringsanlegg</li>
								<li class="formaal_inkludert">2100 Trase for tekn. infrastr.</li>
								<li class="formaal_inkludert">2800 Kombinerte formål samf., infrastr.</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>12 Blå/grønnstruktur</summary>
							<ul class="m-0">
								<li>3002 Blå/grønnstruktur</li>
								<li>3020 Naturområde</li>
								<li>3030 Turdrag</li>
								<li>3040 Friområde</li>
								<li>3050 Park</li>
								<li>3100 Overvannstiltak</li>
								<li>3800 Kombinerte grøntstrukturformål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>13 Forsvaret</summary>
							<ul class="m-0">
								<li>4001 Forsvaret</li>
								<li>4010 Ulike typer militære formål</li>
								<li>4020 Skytefelt/øvingsområde</li>
								<li>4030 Forlegning/leir</li>
								<li>4800 Kombinerte militærformål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>14 LNFR</summary>
							<ul class="m-0">
								<li>5100 LNRF tiltak for stedbunden næring</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>15 LNFR spredt</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">5200 LNFR spredt bolig- fritids eller næringsbebyggelse</li>
								<li>5210 Spredt boligbebyggelse</li>
								<li>5220 Spredt fritidsbebyggelse</li>
								<li>5230 Spredt næringsbebyggelse</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>16 Havner og småbåthavner</summary>
							<ul class="m-0">
								<li class="formaal_inkludert">2040 Havn</li>
								<li class="formaal_inkludert">2044 Molo</li>
								<li>6110 Ankringsområde</li>
								<li>6120 Opplagsområde</li>
								<li>6130 Riggområde</li>
								<li>6220 Havneområde i sjø</li>
								<li>6230 Sjø, vassdrag Småbåthavn</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>17 Sjø og vassdrag, flerbruk</summary>
							<ul class="m-0">
								<li>6001 Bruk og vern av sjø, vassdrag, strandsone</li>
								<li>6100 Sjø, vassdrag Ferdsel</li>
								<li>6110 Ankringsområder</li>
								<li>6120 Opplagsområde</li>
								<li>6130 Riggområde</li>
								<li>6200 Sjø, vassdrag Farleder</li>
								<li>6300 Sjø, vassdrag Fiske</li>
								<li>6500 Sjø, vassdrag Drikkevann</li>
								<li>6600 Sjø, vassdrag Naturområde</li>
								<li>6700 Sjø, vassdrag Friluftsområde</li>
								<li>6800 Sjø, vassdrag Kombinerte formål</li>
							</ul>
						</details>
						<details class="mb-1">
							<summary>18 Akvakultur</summary>
							<ul class="m-0">
								<li>6400 Sjø, vassdrag Akvakultur</li>
							</ul>
						</details>
						<details class="mb-5">
							<summary>19 Udefinert</summary>
							<ul class="m-0">
								<li>9999 Udefinert</li>
							</ul>
						</details>

						<hr class="my-5" />

						<h4 class="text-muted">Last ned resultatdata</h4>
						<p class="mb-4">
							Her kan du laste ned regnskapets kart-data i Shape (.shp) format. <br />
							Dataene inkluderer regnskapets plandata, med data for potensielle arealkonflikter vedlagt i felt-verdiene.
						</p>
						<button type="button" class="btn btn-outline-secondary mb-4" id="result_download">Last ned her</button>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Lukk</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="reportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="reportModalLabel">Hent ut rapport</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col mb-3">
									<p class="lead">Velg ønskede innstillinger for rapporten og trykk "Skriv ut".</p>
								</div>
							</div>

							<div class="row">
								<div class="col mb-3">
									<label for="format" class="form-label">Velg rapportens format</label>
									<select class="form-select form-select-sm" id="format" aria-label="Rapportens format" style="width: auto;">
										<option value="pdf" selected>PDF (.pdf)</option>
										<option value="docx">Word (.docx)</option>
										<option value="xlsx">Excel (.xlsx)</option>
									</select>
								</div>
							</div>

							<div class="row">
								<div class="col mb-3">
									<label for="rapport_kommentar" class="form-label small">
										Legg inn kommentar
										&nbsp;
										<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Dette er en generell kommentar/bemerkning som blir lagt ved rapporten. Dette kan for eksempel gjelde plandataene i sin helhet, utformingen av arealreserven, spesielle hensyn som er tatt i regnskapet, eller andre lignende bemerkninger."></i>
									</label>
									<textarea class="form-control form-control-sm" id="rapport_kommentar" name="rapport_kommentar" rows="4"></textarea>
								</div>
							</div>

							<hr class="my-5" />

							<div class="row">
								<div class="col mb-3">
									<p class="lead mb-1">Fremhevede planområder</p>
									<p class="mb-4">
										Under finnes en oversikt over spesielle planområder som vil fremheves i rapporten. <br />
										Dette er typisk områder med spesielle hensyn i planen. <br />
										Du kan legge ved ytterligere kommentarer til hvert område i listen.
									</p>
								</div>
							</div>

							<div class="row">
								<div class="col mb-3">
									<table class="table table-hover" id="report_obj_table">
										<caption class="small">Velg et område <strong>på kartet</strong> og legg til ved å trykke <i class="fa-solid fa-print"></i> ikonet</caption>
										<thead>
											<tr>
												<th scope="col">&nbsp;</th>
												<th scope="col">ID</th>
												<th scope="col">Arealformål</th>
												<th scope="col">Ikrafttredelsesdato</th>
												<th scope="col">Plannavn</th>
												<th scope="col">&nbsp;</th>
											</tr>
										</thead>
										<tbody class="table-group-divider"></tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Lukk</button>
						<a href="assets/ARV rapport demo.pdf" target="_blank"><button type="button" class="btn btn-secondary" id="report_download">Lag rapport</button></a>
					</div>
				</div>
			</div>
		</div>

		<div id="mapCont">
			<div class="h-100" id="map"></div>

			<div class="h-0" id="report">
				<ul class="nav nav-tabs" id="report_tab" role="tablist">
					<li class="nav-item" role="presentation">
						<button type="button" role="tab" class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" aria-controls="dashboard" aria-selected="true">
							<i class="fa-solid fa-chart-pie"></i>
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button type="button" role="tab" class="nav-link" id="balance-tab" data-bs-toggle="tab" data-bs-target="#balance" aria-controls="balance" aria-selected="false">
							<i class="fa-solid fa-table"></i>
						</button>
					</li>
					<li class="nav-item me-sm-auto" role="presentation">
						<button type="button" role="tab" class="nav-link" id="table-tab" data-bs-toggle="tab" data-bs-target="#table" aria-controls="table" aria-selected="false">
							<i class="fa-solid fa-list"></i>
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button type="button" class="nav-link" id="report_fullscreen">
							<i class="fa-solid fa-chevron-up"></i>
						</button>
					</li>
				</ul>
				<div class="tab-content" id="report_tab_content">
					<div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab" tabindex="0">
						<p class="text-muted mx-3 my-2">Velg et regnskap</p>
					</div>
					<div class="tab-pane fade" id="balance" role="tabpanel" aria-labelledby="balance-tab" tabindex="0">
						<p class="text-muted mx-3 my-2">Velg et regnskap</p>
					</div>
					<div class="tab-pane fade" id="table" role="tabpanel" aria-labelledby="table-tab" tabindex="0">
						<p class="text-muted mx-3 my-2">Velg et regnskap</p>
					</div>
				</div>
			</div>
		</div>

		<div class="px-2 pb-3 shadow" id="section">
			<div class="input-group input-group-sm my-3">
				<button type="button" class="btn btn-outline-secondary dropdown-toggle" id="btnAccountsDrop" data-bs-toggle="dropdown" aria-expanded="false" disabled>
					<i class="fa-solid fa-ellipsis-vertical"></i>
				</button>
				<ul class="dropdown-menu" aria-labelledby="btnAccountsDrop">
					<li>
						<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#reportModal">Hent ut rapport</button>
					</li>
					<li>
						<!--a class="dropdown-item" href="Dokumentasjon.pdf" target="_blank">Vis dokumentasjon</a-->
						<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#docModal">Vis dokumentasjon</button>
					</li>
					<li><hr class="dropdown-divider" /></li>
					<li>
						<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal">Rediger</button>
					</li>
				</ul>

				<select class="form-select" id="accounts" aria-label="Accounts">
		<?php if($count > 0) { ?>
					<option selected disabled>Velg regnskap</option>
			<?php foreach($rows as $row) { ?>
					<option
						value="<?php echo $row['id']; ?>"
						data-title="<?php echo $row['title']; ?>"
						data-description="<?php echo $row['description']; ?>"
						data-thumbnail="<?php echo $row['thumbnail']; ?>"
						data-planfiler="<?php echo $row['planfiler']; ?>"
						data-created_date="<?php echo $row['created_date']; ?>"
					>
						<?php echo $row['title']; ?>
					</option>
			<?php } ?>
		<?php } else { ?>
					<option selected disabled>Ingen regnskap funnet</option>
		<?php } ?>
				</select>
			</div>


			<h3 class="mb-3">
				<strong id="accounts_title"></strong>
			</h3>


			<p class="small mb-1">
				Arealreserven i planene
				&nbsp;
				<i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder som er planlagt utbygd, men hvor utbyggingen enda ikke er ferdigstilt. <br /> Fremstilling er basert på det valgte datafeltet"></i>
			</p>

			<span class="small">Velg fremstilling:</span>
			<select class="form-select form-select-sm d-inline mb-1" id="arealreserve" aria-label="Arealreserve fremstilling" style="width: 150px;" disabled>
				<option value="arealformaalsgruppe" selected>Formålsgruppe</option>
				<option value="arealklasse">Formålsklasse</option>
				<option value="planalder">Planalder</option>
				<option value="plankilde">Plankilde</option>
				<option value="arealstatus">Arealstatus</option>
				<option value="plantype">Plantype</option>
				<option value="planstatus">Planstatus</option>
				<option value="planbestemmelse">Planbestemmelse</option>
				<option value="eierform">Eierform</option>
				<option value="lovreferanse">Lovreferanse</option>
			</select>

			<div class="small mb-3" id="arealreserveColors">
				<span class="color-preview" id="planlagt_01"></span> 01 Bolig eller sentrumsformål <br />
				<span class="color-preview" id="planlagt_02"></span> 02 Fritidsbebyggelse          <br />
				<span class="color-preview" id="planlagt_03"></span> 03 Tjenesteyting              <br />
				<span class="color-preview" id="planlagt_04"></span> 04 Handel                     <br />
				<span class="color-preview" id="planlagt_05"></span> 05 Turistformål               <br />
				<span class="color-preview" id="planlagt_06"></span> 06 Næringsvirksomhet          <br />
				<span class="color-preview" id="planlagt_07"></span> 07 Råstoffutvinning           <br />
				<span class="color-preview" id="planlagt_08"></span> 08 Kombinerte formål          <br />
				<!--span class="color-preview" id="planlagt_09"></span> 09 Idrettsanlegg              <br /-->
				<!--span class="color-preview" id="planlagt_10"></span> 10 Andre formål               <br /-->
				<!--span class="color-preview" id="planlagt_11"></span> 11 Samferdselsanlegg          <br /-->
				<span class="color-preview" id="planlagt_12"></span> 12 Blå/grønnstruktur          <br />
				<span class="color-preview" id="planlagt_13"></span> 13 Forsvaret                  <br />
				<span class="color-preview" id="planlagt_14"></span> 14 LNFR                       <br />
				<span class="color-preview" id="planlagt_15"></span> 15 LNFR spredt                <br />
				<span class="color-preview" id="planlagt_16"></span> 16 Havner og småbåthavner     <br />
				<span class="color-preview" id="planlagt_17"></span> 17 Sjø og vassdrag, flerbruk  <br />
				<span class="color-preview" id="planlagt_18"></span> 18 Akvakultur                 <br />
				<span class="color-preview" id="planlagt_19"></span> 19 Udefinert
			</div>


			<p class="small mb-1">
				<span class="color-preview" id="overlapp"></span> Områder med potensiell arealkonflikt
				&nbsp;
				<i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Potensielle arealkonflikter mellom planområder og særlig viktige arealer"></i>
			</p>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="aapen_fastmark" data-layer="åpen fastmark" value="åpen fastmark" autocomplete="off" disabled />
				<label class="form-check-label" for="aapen_fastmark">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 50-Åpen fastmark"></i>
					Åpen fastmark
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="myr" data-layer="myr" value="myr" autocomplete="off" disabled />
				<label class="form-check-label" for="myr">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 60-Myr"></i>
					Myr
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="skog" data-layer="skog" value="skog" autocomplete="off" disabled />
				<label class="form-check-label" for="skog">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
					Skog
				</label>
			</div>
				<div class="form-check form-control-sm ms-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_barskog" data-layer="barskog" data-parentlayer="skog" value="barskog" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_barskog" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Barskog
					</label>
				</div>
					<!--div class="form-check form-control-sm ms-2 m-0 py-0">
						<input type="checkbox" class="form-check-input" id="skog" data-layer="skog" value="skog" autocomplete="off" disabled />
						<label class="form-check-label text-muted" for="skog" style="font-size: 12px;">
							&nbsp;
							<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
							Særs høy bonitet
						</label>
					</div-->
				<div class="form-check form-control-sm ms-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_lauvskog" data-layer="lauvskog" data-parentlayer="skog" value="lauvskog" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_lauvskog" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Løvskog
					</label>
				</div>
				<div class="form-check form-control-sm ms-2 mb-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_blanding" data-layer="blanding" data-parentlayer="skog" value="blanding" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_blanding" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Blanding
					</label>
				</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="jordbruk" data-layer="jordbruk" value="jordbruk" autocomplete="off" disabled />
				<label class="form-check-label" for="jordbruk">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 21-Fulldyrka jord, 22-Overflatedyrka jord eller 23-Innmarksbeite"></i>
					Jordbruk
				</label>
			</div>
				<div class="form-check form-control-sm ms-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_fulldyrket" data-layer="fulldyrket" data-parentlayer="jordbruk" value="fulldyrket" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_fulldyrket" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Fulldyrket jord
					</label>
				</div>
				<div class="form-check form-control-sm ms-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_overflatedyrket" data-layer="overflatedyrket" data-parentlayer="jordbruk" value="overflatedyrket" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_overflatedyrket" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Overflatedyrket jord
					</label>
				</div>
				<div class="form-check form-control-sm ms-2 m-0 py-0">
					<input type="checkbox" class="form-check-input" id="_innmarksbeite" data-layer="innmarksbeite" data-parentlayer="jordbruk" value="innmarksbeite" autocomplete="off" disabled />
					<label class="form-check-label text-muted" for="_innmarksbeite" style="font-size: 12px;">
						&nbsp;
						<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra FKB-AR5, arealtype 30-Skog"></i>
						Innmarksbeite
					</label>
				</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="kulturlandskap" data-layer="kulturlandskap" value="kulturlandskap" autocomplete="off" disabled />
				<label class="form-check-label" for="kulturlandskap">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra Miljødirektoratets datasett, Verdifulle kulturlandskap"></i>
					Kulturlandskap
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="strandsone" data-layer="strandsone" value="strandsone" autocomplete="off" disabled />
				<label class="form-check-label" for="strandsone">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra SSBs data for Potensielt tilgjengelig strandsone"></i>
					Strandsone
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="over_skoggrense" data-layer="over skoggrense" value="over skoggrense" autocomplete="off" disabled />
				<label class="form-check-label" for="over_skoggrense">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra Miljødirektoratets Natur i Norge (NiN) landskapstyper data, vegetasjon 'KLG-VE-4 Bart fjell over skoggrensen' eller 'KLG-VE-3 Hei over skoggrensen'"></i>
					Over skoggrensen
				</label>
			</div>

			<hr class="my-2" />

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="skredfaresone" data-layer="skredfaresone" value="skredfaresone" autocomplete="off" disabled />
				<label class="form-check-label" for="skredfaresone">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra NVEs data for 100-års skredfaresoner"></i>
					Skredfaresone
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="flomsone" data-layer="flomsone" value="flomsone" autocomplete="off" disabled />
				<label class="form-check-label" for="flomsone">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra NVEs data for 10-års flomsoner"></i>
					Flomsone
				</label>
			</div>

			<div class="form-check form-control-sm m-0 py-0">
				<input type="checkbox" class="form-check-input" id="villrein" data-layer="villrein" value="villrein" autocomplete="off" disabled />
				<label class="form-check-label" for="villrein">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra Miljødirektoratets datasett, Villreinområder"></i>
					Villreinområder
				</label>
			</div>

			<div class="form-check form-control-sm m-0 mb-4 py-0">
				<input type="checkbox" class="form-check-input" id="iba" data-layer="iba" value="iba" autocomplete="off" disabled />
				<label class="form-check-label" for="iba">
					&nbsp;
					<i class="fa-solid fa-circle-info small" data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Områder hentet fra Important Bird Areas på birdlife.no"></i>
					Viktige fugleområder
				</label>
			</div>


			<p class="mb-1">
				Laget med <a class="text-dark" href="https://geotales.io" target="_blank"><img src="https://geotales.io/assets/logo.png" width="auto" height="20" />eoTales</a>
			</p>
		</div>
	</main>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

	<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" />

	<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

	<script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js"></script>

	<script type="text/javascript" src="lib/moment.js"></script>
	<script type="text/javascript" src="lib/turf.min.js"></script>

	<script type="text/javascript" src="js/helper.js"></script>
	<script type="text/javascript" src="js/CustomControl.js"></script>
	<script type="text/javascript" src="js/CustomResizeObserver.js"></script>

	<script type="text/javascript" src="js/report.js"></script>

	<script type="text/javascript">

		const _ORG_INFO = {
				"name": `<?php echo $org["name"]; ?>`,
				"org.nr.": `<?php echo $org["org.nr."]; ?>`,
				"contact": `<?php echo $org["contact"]; ?>`,
				"fylkesnummer": `<?php echo $org["fylkesnummer"]; ?>`,
				"kommunenummer": `<?php echo $org["kommunenummer"]; ?>`
			  },
			  _USER_INFO = {
			  	"username": `<?php echo getUsername($user_id); ?>`,
			  	"email": `<?php echo getUserEmail($user_id); ?>`
			  };

		const csrf_token = `<?php echo $csrf_token; ?>`,
			  mapBounds = [[-22.951570821478555, 54.933532152245874], [48.58927731671932, 72.73727872150113]],
			  animTime = 750;

		let _ACCOUNT_INFO,
			_PDF,
			_MAP,
			_MAP_INDEX,
			_POPUP,
			_DATA,
			_GRAPHS,
			_LAYERS;
		window.addEventListener("load", function() {

			$.ajaxSetup({ timeout: 0 });

			$.ajax({
				type: "POST",
				url: "api.php",
				data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : null },
				success: function(result, status, xhr) { console.log("Analytics registered"); },
				error: function(xhr, status, error) { console.log("Analytics failed", xhr.status, error); }
			});

			let tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
			let tooltipList = tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

			// Disable Bootstrap tab keyboard-navigation
			$("#report_tab button").click(ev => { document.activeElement.blur(); });

			// Disable mobile pinch-zoom
			document.addEventListener("touchmove", ev => {
				if(ev.scale !== 1) { ev.preventDefault(); }
			}, false);

			// Resize funksjon for venstre-panel
			let leftResize = new CustomResizeObserver({
				element: "#section",
				endEvent: () => _MAP.resize(),
				resizeFunction: () => {
					let w = $("#section").outerWidth();
					$("#mapCont").width(`calc(100% - ${w}px)`);
				}
			});

			// Resize funksjon for rapport-område (bruker #map for å resize-e)
			let reportResize = new CustomResizeObserver({
				element: "#map",
				endEvent: () => _MAP.resize(),
				resizeFunction: () => {
					let h = $("#map").outerHeight();
					$("#report").height(`calc(100% - ${h}px)`);
				}
			});

			// Set up map-button fade-out
			let btnTimer = null,
				btnToggle = v => { $(".mapboxgl-ctrl").css("opacity", v || 0); };
			$("#map").on("mousemove touchmove", function() {
				btnToggle(1); clearTimeout(btnTimer);
				btnTimer = setTimeout(btnToggle, 4000);
			});
			setTimeout(btnToggle, 4000);

			Highcharts.setOptions({
				credits: { enabled: false },
				accessibility: { announceNewData: { enabled: true } },
				legend: { enabled: false },
				chart: {
					/*backgroundColor: {
						linearGradient: [0, 0, 500, 500],
						stops: [
							[0, 'rgb(255, 255, 255)'], [1, 'rgb(240, 240, 255)']
						]
					},*/
					//borderWidth: 1,
					//borderColor: "grey",
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false
				},
				xAxis: {
					type: "category",
					gridLineWidth: 1,
					lineWidth: 0
				},
				yAxis: {
					min: 0,
					title: { text: "Areal (daa)" },
					labels: { overflow: "justify" },
					gridLineWidth: 0
				},
				plotOptions: {
					series: {
						allowPointSelect: true,
						cursor: "pointer",
						borderRadius: 5,
						dataLabels: [
							{ enabled: true, distance: 6 }
						]
					},
					pie: {},
					bar: { groupPadding: 0.1 },
					sunburst: {
						dataLabels: {
							formatter: function() { return this.point.name.slice(0,4); },
							filter: {
								property: "innerArcLength", operator: ">", value: 16
							}
						}
					}
				},
				drilldown: {
					activeDataLabelStyle: { textDecoration: "none" },
					breadcrumbs: { buttonSpacing: 1 }
				},
				exporting: {
					buttons: {
						contextButton: {
							menuItems: [ "downloadPNG", "downloadJPEG", "downloadSVG", "downloadPDF", "separator", "downloadCSV", "downloadXLS" ]
						}
					},
					chartOptions: {
						plotOptions: {
							series: {
								dataLabels: { enabled: true }
							},
							pie: {
								dataLabels: {
									useHTML: true,
									formatter: function() {
										let p = this.point;
										return `${p.name}<br /><span style="font-size:8px;">${p.y.toLocaleString()} daa</span>`;
									}
								}
							}
						},
						//subtitle: { text: "" }
					},
					//fallbackToExportServer: true
				}
			});

			$.extend($.fn.dataTable.defaults, {
				info: false,
				paging: false,
				searching: false,
				lengthChange: false,
				autoWidth: false
			});

			moment.locale("nb");



			_PDF = null;

			// Outdoors-mute: mapbox://styles/andreasatakan/ckr3zunbt18f517pewgf0ewaa
			// Satellite: mapbox://styles/andreasatakan/clhf9cjsu019f01qygzstdcee
			let curr_style = "mapbox://styles/andreasatakan/ckr3zunbt18f517pewgf0ewaa";

			mapboxgl.accessToken = "pk.eyJ1IjoiYW5kcmVhc2F0YWthbiIsImEiOiJjbGhmOWJwY2YxbnV6M2xwYzZ2NW9jYTRtIn0.okXO5qA-OcCpQbGRZuHv6w";
			_MAP = new mapboxgl.Map({
				container: "map",
				style: curr_style,
				//projection: "mercator",
				center: [ 12.81885324762095, 65.2870238834735 ],
				zoom: 3.6,
				maxPitch: 60,
				maxBounds: mapBounds,
				customAttribution: "Laget med <a href=\"https://geotales.io/\" target=\"_blank\">GeoTales</a>",
				logoPosition: "bottom-right",
				keyboard: false,
				language: "no"
			});
			_POPUP = new mapboxgl.Popup({
				anchor: "bottom",
				maxWidth: "none",
				focusAfterOpen: false
			});

			mapRegisterControlPosition(_MAP, "bottom-center");



			_MAP_INDEX = 0;
			_DATA = {};
			_GRAPHS = {};
			_LAYERS = [ "plandata", "myr", "skog", "jordbruk", "åpen fastmark", "kulturlandskap", "skredfaresone", "flomsone", "over skoggrense", "strandsone", "villrein", "iba" ];

			function updateMetadata() {
				if(!_ACCOUNT_INFO) { return; }

				$("#editModal #title").val( _ACCOUNT_INFO.title );
				$("#editModal #description").val( _ACCOUNT_INFO.description );
				$("#editModal button#save").data( "id", _ACCOUNT_INFO.id );
				$("#deleteModal button#delete").data( "id", _ACCOUNT_INFO.id );

				$("#docModal #title").html( _ACCOUNT_INFO.title );
				$("#docModal #description").html( _ACCOUNT_INFO.description );
				$("#docModal #created_date").html( moment( _ACCOUNT_INFO.created_date ).format("DD MMM YYYY") );

				$("#docModal #planfiler_divider").prevAll().remove();
				for(let planfil of _ACCOUNT_INFO.planfiler.split(",")) {
					if(!planfil) { continue }
					$("#docModal #planfiler_divider").before(`
						<tr>
							<td scope="row">${planfil}</td>
							<td>Kommunen</td>
							<td id="plandata_date">-</td>
							<td>
								1. Klargjøre og sammenstille, <br />
								2. Beregne arealformålsendringer
							</td>
						</tr>
					`);
				}
				$("#docModal #plandata_date").html( moment( _ACCOUNT_INFO.created_date ).format("MMMM YYYY") );

				$("#accounts_title").html( _ACCOUNT_INFO.title );
			}

			let format_area = n => formatDecimal(n, 1);
			function filterOverlapp(data) {
				let active = [], d = { ...data };

				for(let l of _LAYERS.slice(1)) {
					if( $(`#section input[data-layer="${l}"]`)[0].checked ) { active.push(l); }
				}
				for(let p in d) {
					if(active.indexOf( p.replace("_", " ").replace("aa", "å") ) < 0) { delete d[p]; }
				}

				return d;
			}

			let reset_overlapp_graph;
			function initDashboard() {
				let plandata = _DATA.plandata.features.map(f => f.properties),
					overlapp = _DATA.overlapp.features.map(f => f.properties),
					data;


				//
				$("#report #dashboard").html(`
					<div class="graph" id="formaal_pie"></div>
					<div class="graph" id="overlapp_bar"></div>
					<button type="button" class="btn btn-sm btn-outline-secondary graph m-5" id="add">
						<i class="fa-solid fa-plus"></i>
					</button>
				`);

				data = groupArray(plandata, "arealformaalsgruppe", "planlagt_m2");
				let drilldown = groupArray(plandata, "arealformaalsbeskrivelse", "planlagt_m2");
				_GRAPHS["formaal_pie"] = Highcharts.chart("formaal_pie", {
					chart: {
						type: "pie",
						events: {
							drilldown: ev => {
								let n = ev.point.name;
								let d = groupArray(overlapp.filter(e => _AREALFORMAALSGRUPPER[n].indexOf(e.formaal) > -1), "overlapp_type", "areal_m2");
								_GRAPHS["overlapp_bar"].__origData = d;
								d = filterOverlapp(d);
								_GRAPHS["overlapp_bar"].series[0].setData( Object.keys(d).map(e => [ e, format_area(d[e] / 1000) ]) );
								_GRAPHS["overlapp_bar"].setSubtitle({ text: `Arealer for gruppen ${n}` });
							},
							drillup: ev => { reset_overlapp_graph(); }
						}
					},
					title: { text: "Formålsgrupper", align: "left" },
					subtitle: {
						text: "Trykk på en av gruppene for å se nøyaktig arealformål", //`${Object.keys(data).reduce((t, e) => t + Math.round(data[e]), 0)} m2`,
						align: "left"
					},
					tooltip: { valueSuffix: " daa" },
					exporting: { chartOptions: { subtitle: { text: "" } } },
					series: [
						{ name: "Areal", data: Object.keys(data).map(e => ({ name: e, y: format_area(data[e] / 1000), drilldown: e })) }
					],
					drilldown: {
						series: Object.keys(data).map(e => ({
							name: e,
							id: e,
							data: Object.keys(drilldown)
								.filter(d => _AREALFORMAALSGRUPPER[e].indexOf(d) > -1)
								.map(d => ({
									name: d,
									y: format_area(drilldown[d] / 1000),
									color: _PLAN_COLORS["arealformaalsgruppe"][e]
								}))
						}))
					},
					colors: Object.keys(data).map(e => _PLAN_COLORS["arealformaalsgruppe"][e])
				});


				data = groupArray(overlapp, "overlapp_type", "areal_m2");
				let d = filterOverlapp(data);
				_GRAPHS["overlapp_bar"] = Highcharts.chart("overlapp_bar", {
					chart: { type: "bar" },
					title: { text: "Potensiell arealkonflikt", align: "left" },
					subtitle: {
						text: "Viser sammenlagt areal for hele regnskapet",
						align: "left"
					},
					tooltip: { valueSuffix: " daa" },
					lang: { noData: "Velg arealkonflikter i avhukingsboksene til venstre" },
					plotOptions: { series: { allowPointSelect: false } },
					legend: { enabled: false },
					exporting: {
						chartOptions: { lang: { noData: "Ingen valgte arealkonflikter" } }
					},
					series: [
						{ name: "Areal", data: Object.keys(d).map(e => [ e, format_area(d[e] / 1000) ]) }
					],
					colors: Object.keys(data).map(e => _OVERLAPP_COLOR)
				});
				_GRAPHS["overlapp_bar"].__origData = data;
				reset_overlapp_graph = () => {
					let d = groupArray(overlapp, "overlapp_type", "areal_m2");
					_GRAPHS["overlapp_bar"].__origData = d;
					d = filterOverlapp(d);
					_GRAPHS["overlapp_bar"].series[0].setData( Object.keys(d).map(e => [ e, format_area(d[e] / 1000) ]) );
					_GRAPHS["overlapp_bar"].setSubtitle({ text: "Viser sammenlagt areal for hele regnskapet" });
				}


				$("#report #dashboard #add").click(ev => {
					$("#report #dashboard #add").prop("disabled", true);

					let fields = Object.keys(plandata[0]).filter(f => !f.includes("_m2")).filter(f => ["id", "planreservedato", "lokalid", "versjonid"].indexOf(f) < 0),
						area_fields = Object.keys(plandata[0]).filter(f => f.includes("_m2"));
					$("#report #dashboard #add").before(`
						<div class="graph sub container-fluid px-0" id="choose">
							<div class="row g-0">
								<div class="col-6">
									${fields.map(f => `
										<div class="form-check">
											<input type="radio" class="form-check-input x" name="x" id="${f}" value="${f}" />
											<label class="form-check-label" for="${f}">${_FELT[f]}</label>
										</div>
									`).join("")}
								</div>
								<div class="col-6">
									${area_fields.map(f => `
										<div class="form-check">
											<input type="radio" class="form-check-input y" name="y" id="${f}" value="${f}" />
											<label class="form-check-label" for="${f}">${_FELT[f]}</label>
										</div>
									`).join("")}
								</div>
							</div>
						</div>
					`);

					$("#report #dashboard #choose input").change(ev => {
						let x = $("#report #dashboard #choose input.x:checked").val(),
							y = $("#report #dashboard #choose input.y:checked").val();
						if(x && y) {
							let id = "_" + uuid(),
								data = groupArray(plandata, x, y);
							let series = { type: "bar", name: y, data: Object.keys(data).map(e => [ e, format_area(data[e] / 1000) ]) };

							$("#report #dashboard #choose").prop("id", id);

							let title = `${ _FELT[x].replace(" m<sup>2</sup>", "") } – ${ _FELT[y].replace(" m<sup>2</sup>", "") }`;
							_GRAPHS[id] = Highcharts.chart(id, {
								title: { text: title, align: "left" },
								tooltip: { valueSuffix: " daa" },
								series: [ series ],
								colors: _PLAN_COLORS[x] ? Object.values(_PLAN_COLORS[x]).reverse() : Highcharts.defaultOptions.colors,
								exporting: {
									menuItemDefinitions: {
										close: {
											text: "Remove",
											onclick: () => {
												_GRAPHS[id].destroy();
												$(`#report #dashboard #${id}`).remove();
											}
										},
										toggle: {
											text: "Toggle chart type",
											onclick: function() {
												let types = ["bar", "pie", "line", "area"],
													t = types.indexOf(series.type) + 1;
												series.type = types[ t >= types.length ? 0 : t ];
												this.series[0].remove();
												this.addSeries(series);
											}
										}
									},
									buttons: { contextButton: { menuItems: [ "toggle", "separator", "downloadPNG", "downloadJPEG", "downloadSVG", "downloadPDF", "separator", "downloadCSV", "downloadXLS", "separator", "close" ] } }
								}
							});

							$("#report #dashboard #add").prop("disabled", false);
						}
					});
				});


				//
				$("#report #balance").html(`
					<h5 class="mx-3 my-2">Kommunens arealbalanse</h5>
					<p class="text-muted small mx-3 mb-0">Alle tall i dekar</p>
					<table class="table table-striped" id="balance_table">
						<thead></thead>
						<tbody class="table-group-divider"></tbody>
					</table>
				`);

				data = groupArrayMany(plandata.filter(e => e.planlegging_status == "Tidligere plan"), "arealformaalsbeskrivelse", ["myr_m2", "skog_m2", "jordbruk_m2", "aapen_fastmark_m2", "kulturlandskap_m2", "skredfaresone_m2", "flomsone_m2", "over_skoggrense_m2", "strandsone_m2", "villrein_m2", "iba_m2", "planlagt_m2"]);
				let data_all = groupArrayMany(plandata, "arealformaalsbeskrivelse", ["myr_m2", "skog_m2", "jordbruk_m2", "aapen_fastmark_m2", "kulturlandskap_m2", "skredfaresone_m2", "flomsone_m2", "over_skoggrense_m2", "strandsone_m2", "villrein_m2", "iba_m2", "planlagt_m2"]);
				let fp = Object.keys(data)[0];
				$("#balance_table thead").html(`
					<tr>
						<th scope="col">&nbsp;</th>
						${Object.keys(data[fp]).map(p => `
							<th scope="col">${_FELT[p].replace("m<sup>2</sup>", "")}</th>
						`)}
					</tr>
				`);
				for(let r of Object.keys(data).sort()) {
					$("#balance_table tbody").append(`
						<tr>
							<th scope="row">${r}</th>
							${Object.keys(data[r]).map(p => `
								<td>${ format_area(data[r][p] / 1000) }</td>
							`)}
						</tr>
						<tr>
							<th scope="row" class="text-muted small">
								<span data-bs-toggle="tooltip" data-bs-container="main" data-bs-html="true" title="Endring i arealsum fra tidligere plan til ny plan">Arealendring</span>
							</th>
							${Object.keys(data[r]).map(p => {
								let diff = format_area((data_all[r][p] - data[r][p]) / 1000);
								if(diff == 0) { return `<td></td>`; }
								return `<td class="${diff > 0 ? "text-red" : "text-green"}">${diff > 0 ? "+" : ""}${diff}</td>`;
							})}
						</tr>
					`);
				}
				$("#balance_table tbody").append(`
					<tr>
						<th scope="row">SUM</th>
						${Object.keys(data[fp]).map(p => {
							let s = 0;
							for(let r in data) { s += data[r][p]; }
							return `<td>${ format_area(s / 1000) }</td>`;
						})}
					</tr>
					<tr>
						<th scope="row"></th>
						${Object.keys(data[fp]).map(p => {
							let s = 0, s_full = 0;
							for(let r in data) {
								s += data[r][p]; s_full += data_all[r][p];
							}
							let diff = format_area((s_full - s) / 1000);
							return `<td class="${diff > 0 ? "text-red" : "text-green"}">${diff > 0 ? "+" : ""}${diff}</td>`;
						})}
					</tr>
				`);


				//
				if(_GRAPHS["plan_table"]) { _GRAPHS["plan_table"].destroy(); } // For å unngå feilmelding
				$("#report #table").html(`
					<table class="table table-hover" id="plan_table">
						<thead></thead>
						<tbody></tbody>
					</table>
				`);

				data = plandata;
				data.sort((a,b) => a.id - b.id);
				let felt = Object.keys(_FELT).filter(p => p != "areal_m2");
				$("#plan_table thead").html(`
					<tr>${ felt.map(p => `<th>${_FELT[p].replace("m<sup>2</sup>", "daa")}</th>`) }</tr>
				`);
				for(let f of data) {
					$("#plan_table tbody").append(`
						<tr>
							${felt.map(p => {
								let v = f[p];
								if(p.includes("_m2")) { v = format_area(v / 1000); }
								if(["ikraft_dato", "plannmosaikkdato", "planreservedato"].indexOf(p) > -1) {
									v = moment(v, "YYYYMMDD").format("DD MMM YYYY");
								}
								return `<td>${v}</td>`;
							})}
						</tr>
					`);
				}
				_GRAPHS["plan_table"] = new DataTable("#plan_table", {
					paging: false,
					searching: false,
					//scrollX: true,
					//scrollY: "calc(50vh - 175.2px)",
					//scroller: true,
					//scrollResize: true,
					//scrollCollapse: true,
				});
				$("#plan_table tbody tr").click(ev => {
					let id = _GRAPHS["plan_table"].row( $(ev.target) ).data()[0];
					for(let f of _DATA.plandata.features) {
						if(f.properties.id == id) {
							_MAP.fitBounds( turf.bbox(f), { padding: 10 } );
							_MAP.fire("click", {
								lngLat: turf.centroid(f).geometry.coordinates,
								props: f.properties,
								from_plan_table: true
							});
							return;
						}
					}
				});
				$("button[data-bs-toggle=\"tab\"]").on("shown.bs.tab", ev => { if(_GRAPHS["plan_table"]) { _GRAPHS["plan_table"].draw(); } });
			}

			const removeSource = id => _MAP.getSource(id) && _MAP.removeSource(id);
			const removeLayer = id => _MAP.getLayer(id) && _MAP.removeLayer(id);
			const remove = id => { removeLayer(id); removeSource(id); };
			function loadData(data, flag) {
				if(data) { _DATA = data; }
				if(Object.keys(_DATA).length <= 0) { return; }
				for(let d in _DATA) {
					try { _DATA[d] = JSON.parse(_DATA[d]); } catch(e) {}
				}

				let plandata = _DATA["plandata"],
					overlapp = _DATA["overlapp"];
				if(flag || true) {
					let ignore = ["09 Idrettsanlegg", "10 Andre formål", "11 Samferdselsanlegg", "15 LNFR spredt"];
					plandata.features = plandata.features.filter(f => ignore.indexOf(f.properties.arealformaalsgruppe) < 0);
					overlapp.features = overlapp.features.filter(f => ignore.indexOf(f.properties.formaalsgruppe) < 0);
				}

				remove("kommunegrense");
				_MAP.addSource("kommunegrense", {
					"type": "geojson",
					"data": _DATA["kommunegrense"],
					"tolerance": 1
				});
				_MAP.addLayer({
					"id": "kommunegrense",
					"type": "line",
					"source": "kommunegrense",
					"paint": {
						"line-color": "#8A3F4E",
						"line-width": 1
					},
					"layout": { "visibility": "visible" }
				});

				removeLayer("plandata_gjeld"); removeLayer("plandata_frem");
				removeSource("plandata");
				_MAP.addSource("plandata", {
					"type": "geojson",
					"data": plandata,
					"tolerance": 1
				});
				_MAP.addLayer({
					"id": "plandata_gjeld",
					"type": "fill",
					"source": "plandata",
					"filter": [ "==", "planlegging_status", "Tidligere plan" ],
					"paint": {
						"fill-color": "#FEFEFE",
						"fill-opacity": 0.6,
						"fill-outline-color": "#b3b3b3"
					},
					"layout": { "visibility": "visible" }
				});
				_MAP.addLayer({
					"id": "plandata_frem",
					"type": "fill",
					"source": "plandata",
					"filter": [ "==", "planlegging_status", "Ny plan" ],
					"paint": {
						"fill-color": "#FEFEFE",
						"fill-opacity": _MAP_INDEX > 0 ? 1 : 0,
						"fill-outline-color": "#cc2900",
						"fill-opacity-transition": { "duration": animTime }
					},
					"layout": {
						"visibility": _MAP_INDEX > 0 ? "visible" : "none"
					}
				});
				$("#section select#arealreserve").trigger("change");

				for(let type of _LAYERS.slice(1)) {
					let features = overlapp.features.filter(f => f.properties["overlapp_type"] == type);

					removeLayer(`${type}_gjeld`); removeLayer(`${type}_frem`);
					removeSource(type);
					_MAP.addSource(type, {
						"type": "geojson",
						"data": {
							"type": "FeatureCollection",
							"features": features
						},
						"tolerance": 1
					});
					_MAP.addLayer({
						"id": `${type}_gjeld`,
						"type": "fill",
						"source": type,
						"filter": [ "==", "planlegging_status", "Tidligere plan" ],
						"paint": {
							"fill-color": _OVERLAPP_COLOR,
							"fill-opacity": 0.8,
							"fill-outline-color": "#b3b3b3"
						},
						"layout": {
							"visibility": $(`#section input[data-layer="${type}"]`)[0].checked ?
											"visible" :
											"none"
						}
					});
					_MAP.addLayer({
						"id": `${type}_frem`,
						"type": "fill",
						"source": type,
						"filter": [ "==", "planlegging_status", "Ny plan" ],
						"paint": {
							"fill-color": _OVERLAPP_COLOR,
							"fill-opacity": _MAP_INDEX > 0 ? 1 : 0,
							"fill-outline-color": "#b3b3b3",
							"fill-opacity-transition": { "duration": animTime }
						},
						"layout": {
							"visibility": $(`#section input[data-layer="${type}"]`)[0].checked && _MAP_INDEX > 0 ?
											"visible" :
											"none"
						}
					});
				}

				initDashboard();

				$(`#btnAccountsDrop,
				   #section select,
				   #section input[type="checkbox"],
				   .custom-layer-toggle button,
				   .custom-accounts-toggle button.next`).prop("disabled", false);
				$("#planlegging_status_view").parent().removeClass("d-none");

				if(data) { _MAP.fitBounds( turf.bbox(plandata) ); }
			}



			_MAP.addControl(new mapboxgl.NavigationControl());
			_MAP.addControl(new mapboxgl.FullscreenControl({
				container: $("main")[0]
			}));
			_MAP.addControl(new mapboxgl.ScaleControl({
				maxWidth: 80
			}), "bottom-right");
			_MAP.addControl(new MapboxGeocoder({
				accessToken: mapboxgl.accessToken,
				mapboxgl: mapboxgl,
				countries: "no",
				bbox: mapBounds.flat(),
				placeholder: "Søk etter sted",
				marker: false
			}), "top-left");

			let leftpanelOpen = true;
			_MAP.addControl(new CustomBtn({
				className: "custom-left-panel-toggle",
				title: "Skjul venstre pannel",
				icon: "bars",
				eventHandler: ev => {
					let btn = $(".custom-left-panel-toggle button");

					if(leftpanelOpen) {
						// hide
						$("#section").hide();
						$("#mapCont").addClass("w-100");
						btn.prop("title", "Vis venstre pannel");
					}
					else {
						// show
						$("#section").show();
						$("#mapCont").removeClass("w-100");
						btn.prop("title", "Skjul venstre pannel");
					}

					_MAP.resize();
					leftpanelOpen = !leftpanelOpen;
				}
			}), "top-left");

			let reportOpen = false;
			_MAP.addControl(new CustomBtn({
				className: "custom-report-toggle px-2 py-1",
				title: "Åpne rapport panel",
				icon: "chevron-up",
				eventHandler: ev => {
					let btn = $(".custom-report-toggle button"),
						i = $(".custom-report-toggle i");

					if(reportOpen) {
						// hide
						i.removeClass("fa-chevron-down");
						i.addClass("fa-chevron-up");
						btn.prop("title", "Åpne rapport panel");

						$("#map").addClass("h-100");
						$("#report").addClass("h-0");
						//$("#map::after").css("display", "none");
						$("#map").css({ "resize": "none", "max-height": "none" });
					}
					else{
						// show
						i.removeClass("fa-chevron-up");
						i.addClass("fa-chevron-down");
						btn.prop("title", "Lukk rapport panel");

						$("#map, #report").removeClass("h-100 h-0");
						$("#map, #report").height("50%");
						//$("#map::after").css("display", "block");
						$("#map").css({ "resize": "vertical", "max-height": "75%" });

						if(_GRAPHS["plan_table"]) { _GRAPHS["plan_table"].draw(); }
					}

					_MAP.resize();
					reportOpen = !reportOpen;
				}
			}), "bottom-left");

			const setStyle = style => {
				if(style != curr_style) { _MAP.setStyle(style); curr_style = style; }
			};
			_MAP.addControl(new CustomBtn({
				title: "Bytt til basis kart",
				icon: "map",
				eventHandler: ev => setStyle("mapbox://styles/andreasatakan/ckr3zunbt18f517pewgf0ewaa")
			}), "bottom-left");
			_MAP.addControl(new CustomBtn({
				className: "custom-map-control",
				title: "Bytt til satellitt kart",
				icon: "earth-europe",
				eventHandler: ev => setStyle("mapbox://styles/andreasatakan/clhf9cjsu019f01qygzstdcee")
			}), "bottom-left");


			function accountsPrev() {
				if(_MAP_INDEX <= 0) { return; }
				_MAP_INDEX -= 1;
				$(".custom-accounts-toggle button.prev").prop("disabled", true);
				$(".custom-accounts-toggle button.next").prop("disabled", false);
				$("#planlegging_status_view").html("tidligere");

				for(let l of _LAYERS) {
					_MAP.setPaintProperty(`${l}_frem`, "fill-opacity", 0);
					setTimeout(() => {
						_MAP.setLayoutProperty(`${l}_frem`, "visibility", "none");
					}, animTime);
				}
			}
			function accountsNext() {
				if(_MAP_INDEX > 0) { return; }
				_MAP_INDEX += 1;
				$(".custom-accounts-toggle button.prev").prop("disabled", false);
				$(".custom-accounts-toggle button.next").prop("disabled", true);
				$("#planlegging_status_view").html("ny");

				_MAP.setLayoutProperty("plandata_frem", "visibility", "visible");
				_MAP.setPaintProperty("plandata_frem", "fill-opacity", 1);
				for(let l of _LAYERS.slice(1)) {
					_MAP.setLayoutProperty(`${l}_frem`, "visibility",
						$(`#section input[data-layer="${l}"]`)[0].checked ?
							"visible" :
							"none"
					);
					_MAP.setPaintProperty(`${l}_frem`, "fill-opacity", 1);
				}
			}
			_MAP.addControl(new CustomBtnGroup({
				className: "custom-accounts-toggle",
				title: "Veksle mellom regnskap",
				btns: [
					{
						className: "px-5 prev",
						title: "<i class=\"fa-solid fa-chevron-left\"></i>",
						eventHandler: ev => { accountsPrev(); }
					},
					{
						className: "px-5 next",
						title: "<i class=\"fa-solid fa-chevron-right\"></i>",
						eventHandler: ev => { accountsNext(); }
					}
				]
			}), "bottom-center");
			$(".custom-accounts-toggle button").prop("disabled", true);
			$(document).keyup(ev => {
				if($(".custom-accounts-toggle button.prev").prop("disabled")
				&& $(".custom-accounts-toggle button.next").prop("disabled")) { return; }
				switch(ev.keyCode) {
					case 37: accountsPrev(); break;
					case 39: accountsNext(); break;
					default: break;
				}
			});


			let layers_shown = [];
			_MAP.addControl(new CustomBtn({
				className: "custom-layer-toggle",
				title: "Hide maplayers",
				icon: "eye-slash",
				eventHandler: ev => {
					let btn = $(".custom-layer-toggle button"),
						i = $(".custom-layer-toggle i");

					if(layers_shown.length <= 0) {
						// hide
						i.removeClass("fa-eye-slash");
						i.addClass("fa-eye");
						btn.prop("title", "Show maplayers");

						let ls = _MAP.getStyle().layers.filter(l => l.source && l.source != "composite" && l.id != "satellite");
						for(let l of ls) {
							let v = _MAP.getLayoutProperty(l.id, "visibility");
							if(v == "visible") { layers_shown.push(l.id); }
							_MAP.setLayoutProperty(l.id, "visibility", "none");
						}

						$(`#section select,
						   #section input[type="checkbox"],
						   .custom-accounts-toggle button`).prop("disabled", true);
					}
					else{
						// show
						i.removeClass("fa-eye");
						i.addClass("fa-eye-slash");
						btn.prop("title", "Hide maplayers");

						for(let id of layers_shown) {
							_MAP.setLayoutProperty(id, "visibility", "visible");
						}
						layers_shown = [];

						$(`#section select,
						   #section input[type="checkbox"],
						   .custom-accounts-toggle button.${_MAP_INDEX > 0 ? "prev" : "next"}`).prop("disabled", false);
					}
				}
			}), "bottom-right");
			$(".custom-layer-toggle button").prop("disabled", true);


			let report_fullscreen = false;
			$("#report #report_fullscreen").click(ev => {
				if(report_fullscreen) {
					// exit fullscreen
					$("#map").removeClass("h-0");
					$("#report").removeClass("h-100");

					$("#report #report_fullscreen i").removeClass("fa-chevron-down");
					$("#report #report_fullscreen i").addClass("fa-chevron-up");

					_MAP.resize();
				}
				else {
					// enter fullscreen
					$("#map").addClass("h-0");
					$("#report").addClass("h-100");

					$("#report #report_fullscreen i").removeClass("fa-chevron-up");
					$("#report #report_fullscreen i").addClass("fa-chevron-down");
				}

				report_fullscreen = !report_fullscreen;
			});



			_MAP.on("style.load", () => {
				loadData();
			});

			_MAP.on("load", () => {
				let runningDownload = false;
				$("#docModal button#result_download").click(ev => {
					if(runningDownload) { return; } runningDownload = true;

					let el = document.createElement("a"),
						title = _ACCOUNT_INFO.title,
						now = moment().format("DD MMM YYYY");
					let filename = `${title} ${now}`;

					let data;
					filename += ".geojson";
					data = JSON.stringify( _DATA["plandata"] );
					data = "data:application/geo+json;charset=utf-8," + encodeURIComponent(data);

					el.setAttribute("href", data);
					el.setAttribute("download", filename);
					el.style.display = "none";

					document.body.appendChild(el);
					$(el).ready(() => {
						el.click();
						document.body.removeChild(el);
						runningDownload = false;
					});
				});

				let runningPrint = false;
				$("#reportModal button#report_download").click(async ev => {
					if(runningPrint) { return; } runningPrint = true;

					$("#reportModal").modal("hide");
					return;
					$("#loadingModal").modal("show");

					let plandata = _DATA.plandata.features.map(f => f.properties),
						overlapp = _DATA.overlapp.features.map(f => f.properties);

					try {
						await generate_report(plandata, overlapp);
						runningPrint = false;
						setTimeout(() => { $("#loadingModal").modal("hide"); }, 500);
					}
					catch(err) {
						console.log(err);
						setTimeout(() => {
							$("#loadingModal").modal("hide");
							$("#errorModal #errorCode").html(err);
							$("#errorModal").modal("show");
						}, 500);
					}
				});

				$("#editModal button#save").click(ev => {
					let id = _ACCOUNT_INFO.id,
						title = $("#editModal #title").val(),
						description = $("#editModal #description").val();

					$("#editModal").modal("hide");
					$("#loadingModal").modal("show");

					$.ajax({
						type: "POST",
						url: "api.php",
						data: {
							"op": "accounts_edit",
							"csrf_token": csrf_token,
							"id": id,
							"title": title,
							"description": description
						},
						dataType: "json",
						success: function(result, status, xhr) {
							_ACCOUNT_INFO["title"] = title;
							_ACCOUNT_INFO["description"] = description;
							updateMetadata();

							setTimeout(() => { $("#loadingModal").modal("hide"); }, 250);
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							setTimeout(() => {
								$("#loadingModal").modal("hide");
								$("#errorModal #errorCode").html(`${xhr.status} ${error}`);
								$("#errorModal").modal("show");
							}, 250);
						}
					});
				});

				$("#deleteModal button#delete").click(ev => {
					let id = _ACCOUNT_INFO.id;

					$("#editModal").modal("hide");
					$("#loadingModal").modal("show");

					$.ajax({
						type: "POST",
						url: "api.php",
						data: {
							"op": "accounts_delete",
							"csrf_token": csrf_token,
							"id": id
						},
						dataType: "json",
						success: function(result, status, xhr) {
							setTimeout(() => window.location.reload(), 250);
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							setTimeout(() => {
								$("#loadingModal").modal("hide");
								$("#errorModal #errorCode").html(`${xhr.status} ${error}`);
								$("#errorModal").modal("show");
							}, 250);
						}
					});
				});

				$("#section select#accounts").change(ev => {
					let id = $(ev.target).val(),
						title = $("option:selected", ev.target).data("title"),
						description = $("option:selected", ev.target).data("description"),
						thumbnail = $("option:selected", ev.target).data("thumbnail"),
						planfiler = $("option:selected", ev.target).data("planfiler"),
						created_date = $("option:selected", ev.target).data("created_date");

					$("#loadingModal").modal("show");

					$.ajax({
						type: "GET",
						url: "api.php",
						data: {
							"op": "accounts_getdata",
							"csrf_token": csrf_token,
							"id": id
						},
						dataType: "json",
						success: function(result, status, xhr) {
							loadData(result);
							setTimeout(() => {
								_ACCOUNT_INFO = { id, title, description, thumbnail, planfiler, created_date };
								updateMetadata();

								$("#loadingModal").modal("hide");
							}, 250);
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							setTimeout(() => {
								$("#loadingModal").modal("hide");
								$("#errorModal #errorCode").html(`${xhr.status} ${error}`);
								$("#errorModal").modal("show");
							}, 250);
						}
					});
				});

				$("#section select#arealreserve").change(ev => {
					let field = $(ev.target).val();
					let fill = _PLAN_COLORS[ field ],
						f = ["arealstatus"].indexOf(field) > -1 && false;

					["plandata_gjeld", "plandata_frem"].forEach(l => {
						_MAP.setPaintProperty(l, "fill-color", 
							[
								"match", ["get", field],
								...Object.keys(fill)
									.map(e => [ f ? parseInt(e) : e, fill[e] ])
									.flat(),
								"#FEFEFE" // otherwise
							]
						);
					});

					let cont = "";
					for(let f in fill) {
						if(["Nyere enn 2008", "Kommuneplan (delplan)", "KpArealformålOmråde", "KpArealbrukOmråde", "RpArealformålOmråde", "RbFormålOmråde"].indexOf(f) > -1
						|| f === "0" || +f) { continue; }
						cont += `<span class="color-preview" style="background-color: ${fill[f]};"></span> ${f} <br />`;
					}
					$("#arealreserveColors").html(cont);
				});

				_LAYERS.slice(1).forEach(_l => {
					let l = _l.replace(" ", "_").replace("å", "aa");

					$(`#section input#${l}`).change(ev => {
						let layer = $(ev.target).val(),
							checked = ev.target.checked;
						let v = checked ? "visible" : "none";

						_MAP.setLayoutProperty(`${layer}_gjeld`, "visibility", v);
						if(_MAP_INDEX > 0) { _MAP.setLayoutProperty(`${layer}_frem`, "visibility", v); }

						$(`#section input[data-parentlayer="${l}"]`).parent().toggle(checked);

						let d = filterOverlapp( _GRAPHS["overlapp_bar"].__origData );
						_GRAPHS["overlapp_bar"].series[0].setData( Object.keys(d).map(e => [ e, format_area(d[e] / 1000) ]) );
					});

					$(`#section input[data-parentlayer="${l}"]`).parent().hide();
					$(`#section input[data-parentlayer="${l}"]`).change(ev => {
						//
					});
				});

				let overlapp_felt = ["myr_m2", "skog_m2", "jordbruk_m2", "aapen_fastmark_m2", "kulturlandskap_m2", "skredfaresone_m2", "flomsone_m2", "over_skoggrense_m2", "strandsone_m2", "villrein_m2", "iba_m2"];
				[ ..._LAYERS ].reverse()
				.map(l => [`${l}_gjeld`, `${l}_frem`]).flat()
				.forEach(l => 
					_MAP.on("click", l, ev => {
						ev.layer_click = true;
						if(_POPUP.isOpen() && !ev.from_plan_table) { return; }

						let latlng = ev.lngLat,
							props = ev.props || ev.features[0].properties;

						let cont = "";
						for(let p in _FELT) {
							let v = props[p];
							if([ ...overlapp_felt ].indexOf(p) > -1
							|| (!v && v != 0)) { continue; }
							if(["ikrafttredelsesdato", "plannmosaikkdato", "planreservedato"].indexOf(p) > -1) {
								v = moment(v, "YYYYMMDD").format("DD MMM YYYY");
							}
							cont += `<tr><th>${_FELT[p]}</th> <td>${v}</td></tr>`;
						}

						let t = $(`tr[data-id="${props["id"]}"]`).length;
						_POPUP
							.setLngLat(latlng)
							.setHTML(`
								<i class="fa-solid fa-${t ? "check" : "print"}" id="obj_report_add"></i>
								<table style="width:100%;">${cont}</table>
							`)
							.addTo(_MAP);

						$(".mapboxgl-popup #obj_report_add").click(ev => {
							let t = $(`tr[data-id="${props["id"]}"]`).length;
							if(t) { return; }

							$(ev.target).removeClass("fa-print").addClass("fa-check");
							$("#reportModal table#report_obj_table tbody").append(`
								<tr data-id="${props["id"]}">
									<td><i class="fa-solid fa-xmark" id="remove"></i></td>
									<td>${ props["id"] }</td>
									<td>${ props["arealformaalsbeskrivelse"] } <span class="small">(${ format_area(props["planlagt_m2"] / 1000) } daa)</span></td>
									<td>${ moment(props["ikrafttredelsesdato"]).format("DD MMM YYYY") }</td>
									<td>${ props["plannavn"] }</td>
									<td><textarea class="form-control form-control-sm" id="comment" rows="2" placeholder="Kommentar"></textarea></td>
								</tr>
							`);
							$("#reportModal table#report_obj_table tr #remove").off("click");
							$("#reportModal table#report_obj_table tr #remove").click(ev => { $(ev.target).parents("tr").remove(); });
						});
					})
				);

				[ ..._LAYERS ].map(l => `${l}_gjeld`)
				.forEach(l => 
					_MAP.on("click", l, ev => {
						let props = ev.props || ev.features[0].properties;
						if(!ev.outline_set) {
							_MAP.setPaintProperty(l, "fill-outline-color",
								[
									"match", ["get", l == "plandata_gjeld" ? "id" : "planomraade_id"],
									props.id || props.planomraade_id, "#cc0000",
									"#b3b3b3"
								]
							);
							ev.outline_set = true;
						}
					})
				);

				["plandata_gjeld", "plandata_frem"].forEach(l => 
					_MAP.on("click", l, ev => {
						let props = ev.props || ev.features[0].properties;

						let d = {};
						Object.keys(props).filter(e => overlapp_felt.indexOf(e) > -1 && props[e] > 0).map(e => d[ e.replace("_m2", "") ] = props[e]);
						_GRAPHS["overlapp_bar"].__origData = d;
						d = filterOverlapp(d);
						_GRAPHS["overlapp_bar"].series[0].setData( Object.keys(d).map(e => [ e, format_area(d[e] / 1000) ]) );
						_GRAPHS["overlapp_bar"].setSubtitle({ text: `Arealer for område ${props.id}` });
					})
				);

				_MAP.on("click", ev => {
					if(Object.keys(_DATA).length <= 0
					|| ev.layer_click) { return; }

					_LAYERS.map(l => `${l}_gjeld`)
					.forEach( l => _MAP.setPaintProperty(l, "fill-outline-color", "#b3b3b3") );
				});

				_POPUP.on("close", ev => { reset_overlapp_graph(); });
			});



			$("#newModal input#plan").change(ev => {
				let cont = "";
				for(let f of ev.target.files) {
					cont += `
						<li class="list-group-item" data-file="${f.name}">
							<input type="checkbox" class="form-check-input" id="pri" />
							<input type="text" class="form-control form-control-sm ms-2" id="name" value="${f.name}" aria-label="${f.size}" style="max-width: 600px; display: inline;" />
						</li>
					`;
				}
				$("#newModal ul#files").html(cont);
				$("#newModal #regpri_help").removeClass("d-none");
			});

			$("#newModal #create").click(ev => {
				//

				let title = $("#newModal input#title").val(),
					description = $("#newModal textarea#description").val();

				if(!title) { return; }

				$("#newModal").modal("hide");
				$("#loadingModal").modal("show");

				let data = new FormData( $("#newModal form#accounts")[0] ),
					regplanList = {};
				$("#newModal ul#files li").each(function(i) {
					regplanList[ $(this).data("file") ] = {
						"name": $("input#name", this).val(),
						"pri": $("input#pri", this).prop("checked")
					};
				});
				data.append("regplanList", JSON.stringify(regplanList));

				$.ajax({
					type: "POST",
					url: "api.php",
					data: data,
					cache: false,
					contentType: false,
					processData: false,
					dataType: "json",
					/*xhr: function() {
						let myXhr = $.ajaxSettings.xhr();
						if(myXhr.upload) {
							myXhr.upload.addEventListener("progress", e => {
								if(e.lengthComputable) {
									$("progress").attr({ value: e.loaded, max: e.total });
								}
							}, false);
						}
						return myXhr;
					},*/
					success: function(result, status, xhr) {
						console.log("Analysis done");

						$("#section select#accounts option[disabled]").after(
							$("<option>", {
								"text": title,
								"value": result.id,
								"data-title": title,
								"data-description": description,
								"data-thumbnail": "",
								"data-planfiler": "",
								"data-created_date": moment().format()
							})
						);
						$("#section select#accounts").val(result.id).change();
					},
					error: function(xhr, status, error) {
						console.log(xhr.status, error);
						setTimeout(() => {
							$("#loadingModal").modal("hide");
							$("#errorModal #errorCode").html(`${xhr.status} ${error}`);
							$("#errorModal").modal("show");
						}, 250);
					}
				});
			});

		});

	</script>

</body>
</html>

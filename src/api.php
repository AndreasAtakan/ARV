<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

ini_set("display_errors", "On"); ini_set("html_errors", 0); error_reporting(-1);

session_start();

require "init.php";
require_once "helper.php";

if(!isset($_REQUEST["op"])) { http_response_code(422); exit; }
$op = $_REQUEST["op"];



// Public API
if($op == "analytics") {

	if($TESTING) { exit; }

	$user_id = null;
	$page = $_SERVER["HTTP_REFERER"];
	$ip = $_SERVER["REMOTE_ADDR"];
	$agent = $_POST["agent"] ?? $_SERVER["HTTP_USER_AGENT"];

	$geoip = new GeoIp2\Database\Reader("/usr/local/share/geoip/GeoLite2-City.mmdb");
	$record = $geoip->city($ip);

	$location = "{$record->country->name}, {$record->mostSpecificSubdivision->name}, {$record->city->name}";
	$lat = $record->location->latitude;
	$lng = $record->location->longitude;

	if(signedIn()) { $user_id = $_SESSION["user_id"]; }

	$stmt = $PDO->prepare("INSERT INTO \"Analytics\" (user_id, page, location, latitude, longitude, agent) VALUES (?, ?, ?, ?, ?, ?)");
	$stmt->execute([$user_id, $page, $location, $lat, $lng, $agent]);
	exit;

}

else
if($op == "matrikkel_search") {

	$where = "";
	$params = array();

	foreach(array("kommunenummer", "gardsnummer", "bruksnummer") as $v) {
		if(isset($_REQUEST[$v]) && $_REQUEST[$v] != "") {
			$where .= "{$v} = '{$_REQUEST[$v]}' and ";
		}
	}

	if(isset($_REQUEST["eiendomstørrelse"])) {
		$v = $_REQUEST["eiendomstørrelse"];
		if($v == "s") { $where .= "areal_m2 <= 1000 and "; }
		else
		if($v == "m") { $where .= "areal_m2 > 1000 and areal_m2 <= 10000 and "; }
		else
		if($v == "l") { $where .= "areal_m2 > 10000 and areal_m2 <= 50000 and "; }
		else
		if($v == "xl") { $where .= "areal_m2 > 50000 and "; }
	}

	foreach(array("kommuneplan", "områderegulering", "detaljregulering") as $v) {
		if(isset($_REQUEST[$v])) {
			$where .= "utbyggingspotensiale_{$v}_m2 > 0 and ";
		}
	}

	if(isset($_REQUEST["utenkulturminne"])) {
		$where .= "harkulturminne = false and ";
	}

	//

	if(substr($where, -4) == "and ") { $where = substr($where, 0, -4); }

	$stmt = $PDO->prepare("
		SELECT objid, teigid, matrikkelenhetid, matrikkelenhetstype, matrikkelnummertekst, noyaktighetsklasseteig, gardsnummer, bruksnummer, bruksnavn, kommunenummer, kommunenavn, areal_m2, utbyggingspotensiale_m2, aapen_fastmark_m2, myr_m2, skog_m2, jordbruk_m2, kulturlandskap_m2, strandsone_m2, skredfaresone_m2, flomsone_m2, villrein_m2, ST_AsGeoJSON( ST_Transform(representasjonspunkt, 4326) ) as representasjonspunkt
		FROM \"teig\"
		WHERE {$where}
		LIMIT 50
	");
	$stmt->execute();
	$rows = $stmt->fetchAll();
	//$count = $stmt->rowCount();

	echo json_encode($rows);
	exit;

}



// Logged-in API
if(!signedIn()
|| !isset($_REQUEST["csrf_token"])) { http_response_code(401); exit; }
$user_id = $_SESSION["user_id"];
$csrf_token = $_REQUEST["csrf_token"];

if( !validCSRF($csrf_token) ) { http_response_code(500); exit; }


if($op == "accounts_create") {

	if(!isset($_POST["title"])) { http_response_code(422); exit; }

	$title = sanitize($_POST["title"]);
	$description = isset($_POST["description"]) ? sanitize($_POST["description"]) : "";
	$planDate = $_POST["plan_dato"];
	$regplanList = isset($_POST["regplanList"]) ? json_decode($_POST["regplanList"], true) : null;

	/*if(files_empty($_FILES["kommuneplan_gjeldende"])) {
		// Hent gjeldende kommuneplan fra GeoNorge
	}*/

	foreach($_FILES as $plan => $files) {
		$c = count($files["name"]);
		for($i = 0; $i < $c; $i++) {
			if(file_is_empty($files["error"][$i])) { continue; }

			$filename = basename( $files["name"][$i] );
			$filename = $regplanList[ $filename ]["pri"] ? "pri_{$filename}" : $filename;
			$path = "_files/{$filename}";
			if(!validPlanUpload($files, $i, $path)) { http_response_code(500); exit; }

			$r = file_move($files["tmp_name"][$i], $path);
			if(!$r) {
				accountsSafeExit($_FILES);
				http_response_code(500); exit;
			}
		}
	}

	// Kjør Python skript
	$date_arg = escapeshellarg($planDate);
	$r = exec(__DIR__."/analysis.py {$date_arg}", $output, $result_code);
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Lage planmosaikk for kommuneplanene
	$r = $PDO->exec( file_get_contents(__DIR__."/analysis/KDD_PLANMOSAIKK_kom.sql") );
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Lage planmosaikk for reguleringsplanene
	$r = $PDO->exec( file_get_contents(__DIR__."/analysis/KDD_PLANMOSAIKK_reg.sql") );
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Beregne arealformålsendringer fra gjeldende til fremtidig
	$r = $PDO->exec( file_get_contents(__DIR__."/analysis/KDD_AREALFORMAALSENDRINGER.sql") );
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Formater felt-data i planmosaikk
	$r = $PDO->exec( file_get_contents(__DIR__."/analysis/KDD_PLANMOSAIKK_formater.sql") );
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Fjerne alle allerede utbygde områder
	$stmt = $PDO->prepare("SELECT kommunenummer AS knr, fylkesnummer AS fnr FROM \"__planer\" LIMIT 1"); $stmt->execute();
	$row = $stmt->fetch();
	$sql =file_get_contents(__DIR__."/analysis/KDD_PLANRESERVE.sql");
	$sql = str_replace("%k", $row['knr'], $sql);
	$sql = str_replace("%f", $row['fnr'], $sql);
	$r = $PDO->exec($sql);
	if($r === false) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	// Beregne overlapp for samlede plandata
	$r = $PDO->exec("CALL ar5_overlapp('myr', '60');") === false;
	$r = $r || $PDO->exec("CALL ar5_overlapp('skog', '30');") === false;
	$r = $r || $PDO->exec("CALL ar5_overlapp('jordbruk', '21, 22, 23');") === false;
	$r = $r || $PDO->exec("CALL ar5_overlapp('åpen fastmark', '50');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('kulturlandskap', 'kulturlandskap_verdifulle');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('skredfaresone', 'nve_100aar_skredfaresone');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('flomsone', 'nve_10aar_flomsone');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('over skoggrense', 'omraader_over_skoggrense');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('strandsone', 'ssb_strandsone_2023');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('villrein', 'villrein_omraader');") === false;
	$r = $r || $PDO->exec("CALL omraade_overlapp('iba', 'iba_norge_u_svalbard');") === false;
	if($r) { accountsSafeExit($_FILES); http_response_code(500); exit; }

	$PDO->exec("ALTER TABLE \"__planer\" ALTER COLUMN geom TYPE Geometry(MultiPolygon, 4326) USING ST_Transform(geom, 4326)");
	$PDO->exec("ALTER TABLE \"__overlapp\" ALTER COLUMN geom TYPE Geometry(MultiPolygon, 4326) USING ST_Transform(geom, 4326)");
	$PDO->exec("DROP INDEX IF EXISTS \"idx___planer_geometry\"");

	$id = uniqid();
	$plandata = "plan_{$id}";
	$overlapp = "overlapp_{$id}";

	$PDO->exec("ALTER TABLE \"__planer\" RENAME TO \"".$plandata."\"");
	$PDO->exec("ALTER TABLE \"__overlapp\" RENAME TO \"".$overlapp."\"");

	$planfiler = "";
	foreach($regplanList as $k => $v) { $planfiler .= "{$v['name']},"; }
	$planfiler = rtrim($planfiler, ",");

	$stmt = $PDO->prepare("INSERT INTO \"Accounts\" (organization_id, title, description, plandata, overlapp, planfiler) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
	$stmt->execute([ getUserOrganization($user_id), $title, $description, $plandata, $overlapp, $planfiler ]);
	$id = $stmt->fetchColumn();

	accountsSafeExit($_FILES);
	echo json_encode(array( "id" => $id ));
	exit;

}

else
if($op == "accounts_edit") {

	if(!isset($_POST["id"])
	|| !isset($_POST["title"])
	|| !isset($_POST["description"])) { http_response_code(422); exit; }

	$id = $_POST["id"];
	$title = sanitize($_POST["title"]);
	$description = sanitize($_POST["description"]);

	if(!isUserAccountsValid($user_id, $id)) {
		http_response_code(401); exit;
	}

	$stmt = $PDO->prepare("UPDATE \"Accounts\" SET title = ?, description = ? WHERE id = ?");
	$stmt->execute([$title, $description, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}

else
if($op == "accounts_delete") {

	if(!isset($_POST["id"])) { http_response_code(422); exit; }

	$id = $_POST["id"];

	if(!isUserAccountsValid($user_id, $id)) {
		http_response_code(401); exit;
	}

	$stmt = $PDO->prepare("SELECT plandata, overlapp FROM \"Accounts\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();

	$PDO->exec("DROP TABLE IF EXISTS \"".$row['plandata']."\", \"".$row['overlapp']."\"");

	$stmt = $PDO->prepare("DELETE FROM \"Accounts\" WHERE id = ?");
	$stmt->execute([$id]);

	echo json_encode(array("status" => "success"));
	exit;

}

else
if($op == "accounts_getdata") {

	if(!isset($_GET["id"])) { http_response_code(422); exit; }

	$id = $_GET["id"];

	if(!isUserAccountsValid($user_id, $id)) {
		http_response_code(401); exit;
	}

	$stmt = $PDO->prepare("SELECT title, plandata, overlapp FROM \"Accounts\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();

	$stmt = $PDO->prepare("
		SELECT
			json_build_object(
				'type', 'FeatureCollection',
				'features', json_agg(ST_AsGeoJSON(T.*)::json)
			) AS g
		FROM \"".$row['plandata']."\" AS T
	");
	$stmt->execute();
	$r = $stmt->fetch();
	$plandata = $r["g"];

	$stmt = $PDO->prepare("
		SELECT
			json_build_object(
				'type', 'FeatureCollection',
				'features', json_agg(ST_AsGeoJSON(T.*)::json)
			) AS g
		FROM \"".$row['overlapp']."\" AS T
	");
	$stmt->execute();
	$r = $stmt->fetch();
	$overlapp = $r["g"];

	$res = array(
		"plandata" => $plandata,
		"overlapp" => $overlapp
	);

	if(true || str_contains(strtolower($row["title"]), "kommune")) {
		$stmt = $PDO->prepare("
			SELECT
				json_build_object(
					'type', 'FeatureCollection',
					'features', json_agg(ST_AsGeoJSON(T.*)::json)
				) AS g
			FROM
				(
					SELECT ST_Transform(K.geom, 4326) AS geom
					FROM
						\"kommuner\" AS K,
						(SELECT fylkesnummer, kommunenummer FROM \"".$row['plandata']."\" LIMIT 1) AS P
					WHERE
						K.fylkesnummer = P.fylkesnummer AND
						K.kommunenummer = P.kommunenummer
				) AS T
		");
		$stmt->execute();
		$r = $stmt->fetch();
		$grense = $r["g"];

		$res["kommunegrense"] = $grense;
	}

	echo json_encode($res);
	exit;

}

else
if($op == "reset_pw") {

	if(!isset($_POST["username"])
	|| !isset($_POST["email"])) { http_response_code(422); exit; }

	$username = $_POST["username"];
	$email = $_POST["email"];

	if(!validUserEmail($username, $email)) {
		http_response_code(401); exit;
	}

	/* TODO:
	$password = bin2hex(random_bytes(12));

	$stmt = $PDO->prepare("UPDATE \"User\" SET password = ? WHERE username = ? AND email = ?");
	$stmt->execute([pw_hash($password), $username, $email]);

	$subject = "GeoTales: password reset";
	$body = "A password-reset has been triggered on the user {$username} \n The new password is: {$password}";

	sendSESEmail($username, $email, $subject, $body);
	*/

	header("Access-Control-Allow-Origin: *");
	header("location: index.php?return_url={$loc}&password_reset=true");

	exit;

}

else { http_response_code(422); exit; }


exit;

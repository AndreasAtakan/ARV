<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

//session_set_cookie_params(['SameSite' => 'None', 'Secure' => true]);

require_once "init.php";



function sanitize($str) {
	return htmlspecialchars($str);
}

function sane_is_null($v) {
	return is_null($v) || $v == "";
}

function flip_array($a) {
	//
}

function pw_hash($pw) {
	return password_hash($pw, PASSWORD_DEFAULT);
}

function file_is_empty($e) {
	return $e == 4;
}

function files_empty($files) {
	$c = count($files["name"]);
	$t = true;
	for($i = 0; $i < $c; $i++) {
		$t = $t && file_is_empty($files["error"][$i]);
	}
	return $t;
}

function file_move($tmp_path, $path) {
	$root = __DIR__;
	return move_uploaded_file($tmp_path, "{$root}/{$path}");
}

function file_to_db($path, $plan) {
	global $TESTING, $PDO;
	$root = __DIR__;

	$PDO->exec("DROP TABLE IF EXISTS \"__{$plan}\"");

	$port = $TESTING ? "63333" : "5432";
	return exec("ogr2ogr -f PostgreSQL 'PG:host=localhost port={$port} user=postgres password=vleowemnxoyvq dbname=arv' {$root}/{$path} -lco LAUNDER=NO -nlt PROMOTE_TO_MULTI -t_srs EPSG:25833 -nln __{$plan}");
}



function signIn($user_id) {
	$_SESSION['user_id'] = $user_id; // log user in

	// Generate CSRF token
	if(!isset($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
}

function signedIn() {
	global $PDO;
	return isset($_SESSION['user_id'])
		&& validUserID($_SESSION['user_id']);
}

function validCSRF($csrf_token) {
	return isset($_SESSION['csrf_token'])
		&& $csrf_token == $_SESSION['csrf_token'];
}

function validUserID($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['c'] == 1;
}

function validSignIn($email, $password) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT password FROM \"User\" WHERE email = ?");
	$stmt->execute([$email]);
	$row = $stmt->fetch();
	return password_verify($password, $row['password']);
}

function validUserEmail($username, $email) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT COUNT(id) = 1 AS c FROM \"User\" WHERE username = ? AND email = ?");
	$stmt->execute([$username, $email]);
	$row = $stmt->fetch();
	return $row['c'] ?? false;
}

function validAuthCode($auth) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT id FROM \"User\" WHERE auth_code = ?");
	try { $stmt->execute([$auth]); }
	catch(\PDOException $e) { return false; }
	$row = $stmt->fetch();
	return $row['id'] ?? false;
}

/*function registerUser($username, $email, $password) {
	global $PDO;
	$stmt = $PDO->prepare("INSERT INTO \"User\" (username, email, password) VALUES (?, ?, ?) RETURNING id");
	$stmt->execute([$username, $email, pw_hash($password)]);
	$row = $stmt->fetch();
	return $row['id'];
}*/

/*function updateUser($user_id, $username, $email, $photo, $password) {
	global $PDO;
	if(sane_is_null($username)
	&& sane_is_null($email)
	&& sane_is_null($photo)
	&& sane_is_null($password)) { return false; }

	if(!sane_is_null($username)) {
		if(isUsernameRegistered($PDO, $username)
		&& $username != getUsername($PDO, $user_id)) { return false; }

		$stmt = $PDO->prepare("UPDATE \"User\" SET username = ? WHERE id = ?");
		$stmt->execute([$username, $user_id]);
	}
	if(!sane_is_null($email)) {
		$stmt = $PDO->prepare("UPDATE \"User\" SET email = ? WHERE id = ?");
		$stmt->execute([$email, $user_id]);
	}
	if(!sane_is_null($photo)) {
		$stmt = $PDO->prepare("UPDATE \"User\" SET photo = ? WHERE id = ?");
		$stmt->execute([$photo, $user_id]);
	}
	if(!sane_is_null($password)) {
		$pw = $password; mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"User\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $user_id]);
	}

	return true;
}*/



function getUsername($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT username FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['username'];
}

function getUserOrganization($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT organization_id FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['organization_id'];
}

function getUserEmail($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT email FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['email'];
}

function getUserPhoto($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT photo FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	$photo = sane_is_null($row['photo']) ? "assets/user-circle-solid.svg" : $row['photo'];
	return $photo;
}

function isUsernameRegistered($username) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE username = ?");
	$stmt->execute([$username]);
	$row = $stmt->fetch();
	return $row['c'] >= 1;
}

function isUserAccountsValid($user_id, $acc_id) {
	global $PDO;
	$org_id = getUserOrganization($user_id);

	$stmt = $PDO->prepare("SELECT organization_id = ? as c FROM \"Accounts\" WHERE id = ?");
	$stmt->execute([
		getUserOrganization($user_id),
		$acc_id
	]);
	$row = $stmt->fetch();

	return getOrganizationName($org_id) == "__all__" || $row['c'] ?? false;
}



function getOrganizationName($id) {
	return getOrganization($id)["name"];
}

function getOrganization($id) {
	global $PDO;
	$stmt = $PDO->prepare("SELECT * FROM \"Organization\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row;
}



function validPlanUpload($files, $i, $target_path) {
	$root = __DIR__;

	if(file_exists("{$root}/{$target_path}")) { return false; } // file already exists

	if($files["size"][$i] > 500000000) { return false; } // file is too large, larger than 50MB

	$filetype = strtolower( pathinfo(basename($files["name"][$i]), PATHINFO_EXTENSION) );
	if(!in_array($filetype, array("zip", "gpkg", "gml"))) { return false; } // file is incorrect type

	return true;
}

function accountsSafeExit($FILES) {
	global $PDO;
	$root = __DIR__;

	foreach($FILES as $plan => $files) {
		$c = count($files["name"]);
		for($i = 0; $i < $c; $i++) {
			if(file_is_empty($files["error"][$i])) { continue; }

			$filename = basename($files["name"][$i]);
			$path = "{$root}/_files/{$filename}";
			if(file_exists($path)) { unlink($path); }

			$PDO->exec("DROP TABLE IF EXISTS \"__".$plan."\"");
		}
	}
}



function mapCreate($PDO, $user_id, $title, $description, $thumbnail, $password) {
	if(!getUserPaid($PDO, $user_id)
	&& !userMapWithinLimit($PDO, $user_id)) { return false; }

	$stmt = $PDO->prepare("INSERT INTO \"Map\" (title, description) VALUES (?, ?) RETURNING id");
	$stmt->execute([$title, $description]);
	$id = $stmt->fetchColumn();

	$stmt = $PDO->prepare("INSERT INTO \"User_Map\" (user_id, map_id, status) VALUES (?, ?, ?)");
	$stmt->execute([$user_id, $id, "owner"]);

	if(!sane_is_null($thumbnail)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET thumbnail = ? WHERE id = ?");
		$stmt->execute([$thumbnail, $id]);
	}

	if(!sane_is_null($password)) {
		$pw = $password;
		mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"Map\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $id]);
	}

	return $id;
}

function mapUpdate($PDO, $map_id, $title, $description, $thumbnail, $password) {
	if(sane_is_null($title)
	&& sane_is_null($description)
	&& sane_is_null($thumbnail)
	&& sane_is_null($password)) { return false; }

	if(!sane_is_null($title)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET title = ? WHERE id = ?");
		$stmt->execute([$title, $map_id]);
	}
	if(!sane_is_null($description)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET description = ? WHERE id = ?");
		$stmt->execute([$description, $map_id]);
	}
	if(!sane_is_null($thumbnail)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET thumbnail = ? WHERE id = ?");
		$stmt->execute([$thumbnail, $map_id]);
	}
	if(!sane_is_null($password)) {
		$pw = $password;
		mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"Map\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $map_id]);
	}

	return true;
}

function mapDelete($PDO, $map_id) {
	$stmt = $PDO->prepare("DELETE FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	return true;
}

function mapGetThumbnail($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT thumbnail FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['thumbnail'];
}

function mapHasThumbnail($PDO, $map_id) {
	return sane_is_null( mapGetThumbnail($PDO, $map_id) );
}



function userMapCanWrite($PDO, $user_id, $map_id) {
	$stmt = $PDO->prepare("SELECT status IN ('owner', 'editor') AS st FROM \"User_Map\" WHERE user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $map_id]);
	$row = $stmt->fetch();
	return $row['st'] ?? false;
}

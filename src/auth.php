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

// user is already logged in
if( signedIn() ) { header("location: $loc"); exit; }


if(isset($_REQUEST['op'])) {
	$op = $_REQUEST['op'];

	switch($op) {
		/*case "signup":
			if(!isset($_POST['username'])
			|| !isset($_POST['email'])
			|| !isset($_POST['password'])) { break; }

			$username = sanitize($_POST['username']);
			$email = sanitize($_POST['email']);
			$password = sanitize($_POST['password']);

			if(isUsernameRegistered($username)) { http_response_code(500); exit; }

			$user_id = registerUser($username, $email, $password); // register user
			signIn($user_id); // log user in
			break;*/


		case "signin":
			if($TESTING) {
				$stmt = $PDO->prepare("SELECT id FROM \"User\" WHERE username = 'Andreas'");
				$stmt->execute();
				$row = $stmt->fetch();
				signIn( $row['id'] ); // log user in
				break;
			}

			if(!isset($_POST['email'])
			|| !isset($_POST['password'])) { break; }

			$email = $_POST['email'];
			$password = $_POST['password'];

			$check = validSignIn($email, $password);
			if($check) {
				$stmt = $PDO->prepare("SELECT id FROM \"User\" WHERE email = ?");
				$stmt->execute([$email]);
				$row = $stmt->fetch();
				$user_id = $row['id'];

				$stmt = $PDO->prepare("UPDATE \"User\" SET last_signin_date = NOW() WHERE id = ?");
				$stmt->execute([$user_id]);

				signIn($user_id); // log user in
			}
			else { $loc = "login.php?return_url={$loc}&signin_failed=true&email={$email}"; }
			break;


		case "signin_authcode":
			if(!isset($_REQUEST['auth'])) { break; }

			$auth = $_REQUEST['auth'];

			$user_id = validAuthCode($auth);
			if($user_id) {
				$stmt = $PDO->prepare("UPDATE \"User\" SET last_signin_date = NOW() WHERE id = ?");
				$stmt->execute([$user_id]);

				signIn($user_id); // log user in
			}
			else { $loc = "login.php?return_url={$loc}"; }
			break;


		default: http_response_code(422); exit;
	}
}
else { http_response_code(422); exit; }

header("Access-Control-Allow-Origin: *");
header("location: $loc");

exit;

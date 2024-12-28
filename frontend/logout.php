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

if(!signedIn()) { header("location: index.php"); exit; }

if(!isset($_REQUEST['csrf_token'])) { http_response_code(422); exit; }

$csrf_token = $_REQUEST['csrf_token'];
if(!validCSRF($csrf_token)) { http_response_code(401); exit; }


session_destroy();

header("location: index.php");

exit;

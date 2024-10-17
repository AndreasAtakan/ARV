<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

// composer

require 'vendor/autoload.php';



// config

$TESTING = false;
$CONFIG = array(
	"host" => $TESTING ? "http://localhost/arv" : "https://{$_SERVER['SERVER_NAME']}",
	"email" => "contact@geotales.io"
);



// DB init

$host = "";
$port = "";
$user = "";
$pass = "";
$db   = "";
//$charset = "utf8mb4";

$dsn = "pgsql:host=$host;port=$port;dbname=$db;options='--search_path=arv --client_encoding=UTF8'";
$options = array(
	\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
	\PDO::ATTR_EMULATE_PREPARES   => false
);

$PDO = null;
try {
	$PDO = new \PDO($dsn, $user, $pass, $options);
}
catch(\PDOException $e) { throw new \PDOException($e->getMessage(), (int)$e->getCode()); }

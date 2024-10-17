/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

BEGIN;

--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "postgis";

--

CREATE TABLE IF NOT EXISTS "Organization" (
	id uuid DEFAULT uuid_generate_v4(),
	name varchar(250) NOT NULL,
	"org.nr." varchar(20),
	contact varchar(250),
	fylkesnummer varchar(10),
	kommunenummer varchar(10),
	created_date timestamp DEFAULT NOW(),
	PRIMARY KEY (id)
);

--

CREATE TABLE IF NOT EXISTS "User" (
	id uuid DEFAULT uuid_generate_v4(),
	organization_id uuid NOT NULL,
	username varchar(250) NOT NULL,
	email varchar(250) NOT NULL,
	password varchar(255) NOT NULL,
	photo varchar(500),
	auth_code uuid DEFAULT uuid_generate_v4(),
	created_date timestamp DEFAULT NOW(),
	last_signin_date timestamp,
	PRIMARY KEY (id),
	FOREIGN KEY (organization_id) REFERENCES "Organization" (id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "Analytics" (
	id uuid DEFAULT uuid_generate_v4(),
	user_id uuid,
	page varchar(200),
	location varchar(250),
	latitude double precision,
	longitude double precision,
	agent varchar(500),
	created_date timestamp DEFAULT NOW(),
	PRIMARY KEY (id),
	FOREIGN KEY (user_id) REFERENCES "User" (id)
		ON UPDATE CASCADE
);

--

CREATE TABLE IF NOT EXISTS "Accounts" (
	id uuid DEFAULT uuid_generate_v4(),
	organization_id uuid NOT NULL,
	title varchar(250) NOT NULL,
	description text,
	thumbnail varchar(500),
	plandata varchar(18),
	overlapp varchar(22),
	planfiler text,
	created_date timestamp DEFAULT NOW(),
	PRIMARY KEY (id),
	FOREIGN KEY (organization_id) REFERENCES "Organization" (id)
		ON UPDATE CASCADE
);

--

END;

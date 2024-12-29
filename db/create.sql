/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

BEGIN;

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "postgis";

CREATE TABLE IF NOT EXISTS arv."User_Org" (
	id uuid DEFAULT uuid_generate_v4(),
	username varchar(250) NOT NULL,
	org_id varchar(250) NOT NULL,
	kommunenummer varchar(4),
	PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS arv."Accounts" (
	id uuid DEFAULT uuid_generate_v4(),
	org_id uuid NOT NULL,
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

END;

/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

BEGIN;

INSERT INTO "Organization" (name)
VALUES ( 'all' );

INSERT INTO "User" (organization_id, username, email, password)
VALUES (
	( select id from "Organization" ),
	'andreas',
	'andreascan.98@gmail.com',
	'$2y$10$tf7C1saEUZ017XAR6leyFO6KpiX7lGvS3RYEpZ0BHlTPqk5qo6VqS'
);

END;

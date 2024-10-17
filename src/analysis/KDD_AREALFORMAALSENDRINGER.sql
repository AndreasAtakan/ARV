
/*   Arealformålsendringer   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Kommunens planmosaikk finnes i tabell ved navn "__planer"

	Output tabell:
	- Planenes arealformålsendringer vil finnes i tabellen ved navn "__planer"
*/

/* Fjern alle nye planområder som har lik arealformål med korresponderende tidligere planområde */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, p.geom ), 3 ) )
from
	(
		select ST_Union(ny.geom) as geom
		from
			( select arealformaal, geom from "__planer" where planlegging_status = 'Tidligere plan' ) as tidligere,
			( select arealformaal, geom from "__planer" where planlegging_status = 'Ny plan' ) as ny
		where
			tidligere.arealformaal = ny.arealformaal and
			ST_Intersects( tidligere.geom, ny.geom )
	) as p
where
	plan.planlegging_status = 'Ny plan' and
	ST_Intersects( plan.geom, p.geom )
;


/* Fjern områder uten geometri */
delete from "__planer" as plan
where
	planlegging_status = 'Ny plan' and
	(
		ST_IsEmpty(geom) or
		geom is null
	)
;

/* –––––––––––––––––––––––– */

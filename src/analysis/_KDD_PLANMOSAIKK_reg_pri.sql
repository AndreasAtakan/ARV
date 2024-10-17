
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Alle planene finnes i tabell ved navn "__plan"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/


delete from "__planer"
where ST_IsEmpty(geometry) or geometry is null
;

/* Lag romlig indeks */
create index if not exists "planer_geom_geom_idx" on "public"."__planer" using GIST ("geometry")
;





/* Fjern alle overlappende områder fra kommuneplan mellom reguleringsplan og kommuneplan */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geometry, p.geometry ), 3 ) )
from
	(
		select
			p.planlegging_status,
			ST_Union(p.geometry) as geometry
		from
			"__planer" as plan,
			"__planer" as p
		where
			plan.plankilde = 'Kommuneplan' and
			p.plankilde = 'Reguleringsplan' and
			plan.planlegging_status = p.planlegging_status and
			ST_Intersects( plan.geometry, p.geometry ) and
			p.planidentifikasjon = '%s'
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	ST_Intersects( plan.geom, p.geom )
;

/* –––––––––––––––––––––––– */

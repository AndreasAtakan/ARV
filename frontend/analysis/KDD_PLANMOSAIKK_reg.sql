
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Alle planene finnes i tabell ved navn "__plan"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/


/* Fjern alle overlappende områder fra reguleringsplan mellom reguleringsplan og kommuneplan */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, p.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan,
			"__planer" as p
		where
			plan.plankilde = 'Kommuneplan' and
			p.plankilde = 'Reguleringsplan' and
			plan.planlegging_status = p.planlegging_status and
			ST_Intersects( plan.geom, p.geom )
	) as p
where
	not plan.regplan_pri and
	plan.plankilde = 'Reguleringsplan' and
	ST_Intersects( plan.geom, p.geom )
;



/* Fjern alle overlappende områder fra kommuneplan mellom reguleringsplan og kommuneplan */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, p.geom ), 3 ) )
from
	(
		select ST_Union(p.geom) as geom
		from
			"__planer" as plan,
			"__planer" as p
		where
			plan.plankilde = 'Kommuneplan' and
			p.plankilde = 'Reguleringsplan' and
			plan.planlegging_status = p.planlegging_status and
			ST_Intersects( plan.geom, p.geom ) and
			p.regplan_pri
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	ST_Intersects( plan.geom, p.geom )
;





delete from "__planer"
where ST_IsEmpty(geom) or geom is null
;

/* –––––––––––––––––––––––– */

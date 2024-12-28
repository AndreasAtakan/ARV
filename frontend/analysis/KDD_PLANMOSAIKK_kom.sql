
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Alle planene finnes i tabell ved navn "__plan"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/


alter table "__planer" rename column geometry to geom;
alter table "__planer"
	alter column geom type Geometry(MultiPolygon, 25833)
	using ST_Multi( ST_Transform(geom, 25833) )
;
alter table "__planer" alter column ikraft_dato type date using ikraft_dato::date
;





/* Fjern overlappende deler av områder i plan slik at nyeste plan vises */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, p.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan inner join
			"__planer" as p
				on ST_Intersects( plan.geom, p.geom ) and plan.lokalid != p.lokalid
		where
			plan.plankilde = 'Kommuneplan' and p.plankilde = 'Kommuneplan' and
			plan.planlegging_status = 'Tidligere plan' and p.planlegging_status = 'Tidligere plan' and
			plan.ikraft_dato >= p.ikraft_dato
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	plan.planlegging_status = 'Tidligere plan' and
	ST_Intersects( plan.geom, p.geom )
;

update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, p.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan inner join
			"__planer" as p
				on ST_Intersects( plan.geom, p.geom ) and plan.lokalid != p.lokalid
		where
			plan.plankilde = 'Kommuneplan' and p.plankilde = 'Kommuneplan' and
			plan.planlegging_status = 'Ny plan' and p.planlegging_status = 'Ny plan' and
			plan.ikraft_dato >= p.ikraft_dato
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	plan.planlegging_status = 'Ny plan' and
	ST_Intersects( plan.geom, p.geom )
;





/*update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( p.geom, plan.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan,
			"__planer" as p
		where
			plan.plankilde = 'Kommuneplan' and p.plankilde = 'Kommuneplan' and
			plan.planlegging_status = 'Tidligere plan' and p.planlegging_status = 'Tidligere plan' and
			plan.ikraft_dato < p.ikraft_dato and
			ST_Intersects( plan.geom, p.geom )
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	plan.planlegging_status = 'Tidligere plan' and
	ST_Intersects( plan.geom, p.geom )
;*/

/*update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( p.geom, plan.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan,
			"__planer" as p
		where
			plan.plankilde = 'Kommuneplan' and p.plankilde = 'Kommuneplan' and
			plan.planlegging_status = 'Ny plan' and p.planlegging_status = 'Ny plan' and
			plan.ikraft_dato < p.ikraft_dato and
			ST_Intersects( plan.geom, p.geom )
	) as p
where
	plan.plankilde = 'Kommuneplan' and
	plan.planlegging_status = 'Ny plan' and
	ST_Intersects( plan.geom, p.geom )
;*/





delete from "__planer"
where ST_IsEmpty(geom) or geom is null
;

/* –––––––––––––––––––––––– */


/*   Planreserve   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Kommunens planmosaikk finnes i tabell ved navn "__planer"

	Output tabell:
	- Planenes arealreserver vil finnes i tabell ved navn "__planer"
*/

/* Fjern områder som overlapper med SSB Arealbruk */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, omr.geom ), 3 ) )
from
	( select ST_Union(geom) as geom from ssb_arealbruk ) as omr
where ST_Intersects( plan.geom, omr.geom )
;



/* Fjern områder som overlapper med AR5 Ferskvannsområder */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, omr.geom ), 3 ) )
from
	(
		select ST_Union(geom) as geom
		from ar5
		where
			arealtype = '81' and
			ST_Area(geom) > 500
	) as omr
where ST_Intersects( plan.geom, omr.geom )
;



/* Fjern områder som overlapper med FKB-Byggning, -Veg og -Tiltak, som er Bolig, Fritidsbebyggelse eller Næringsvirksomhet */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, omr.geom ), 3 ) )
from
	( select ST_Buffer( ST_Union(geom), 15, 'quad_segs=4' ) as geom from fkb_bygg ) as omr
where
	plan.arealformaalsgruppe in ('01 Bolig eller sentrumsformål', '02 Fritidsbebyggelse', '06 Næringsvirksomhet') and
	ST_Intersects( plan.geom, omr.geom )
;

update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, omr.geom ), 3 ) )
from
	( select ST_Buffer( ST_Union(geom), 15, 'quad_segs=4' ) as geom from fkb_veg ) as omr
where
	plan.arealformaalsgruppe in ('01 Bolig eller sentrumsformål', '02 Fritidsbebyggelse', '06 Næringsvirksomhet') and
	ST_Intersects( plan.geom, omr.geom )
;

update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, omr.geom ), 3 ) )
from
	( select ST_Buffer( ST_Union(geom), 15, 'quad_segs=4' ) as geom from fkb_tiltak ) as omr
where
	plan.arealformaalsgruppe in ('01 Bolig eller sentrumsformål', '02 Fritidsbebyggelse', '06 Næringsvirksomhet') and
	ST_Intersects( plan.geom, omr.geom )
;



/* Fjern områder mindre enn ... basert på formålsgruppe */
delete from "__planer" as plan
where
	ST_IsEmpty(geom) or
	geom is null
;

delete from "__planer" as plan
where
	plan.arealformaalsgruppe in ('01 Bolig eller sentrumsformål', '02 Fritidsbebyggelse', '11 Samferdselsanlegg') and
	ST_Area(geom) < 250
;

delete from "__planer" as plan
where
	plan.arealformaalsgruppe = '06 Næringsvirksomhet' and
	ST_Area(geom) < 1000
;

delete from "__planer" as plan
where
	plan.arealformaalsgruppe not in ('01 Bolig eller sentrumsformål', '02 Fritidsbebyggelse', '06 Næringsvirksomhet', '11 Samferdselsanlegg') and
	ST_Area(geom) < 150
;



/* Fjerner områder mindre enn 100m² og "smale" områder (dvs. lange og tynne områder) */
/*delete from "__planer"
where
	ST_IsEmpty(geom) or
	geom is null or

	/*
		Note: The logic here is that if there is a very thin and long polygon,
			  the radius of the Maximum-Inscribed-Circle will be small and
			  the area will be relatively larger.
	//
	(radius from ST_MaximumInscribedCircle(res_table.geom) ) / ST_Area(geom) < 0.0001
	or
	/*
		Note: The logic here is that a very thin and long polygon will have
			  a considerably larget perimeter than area.
			  See: https://gis.stackexchange.com/a/316133
	//
	ST_Area(geom) / POWER( ST_Perimeter(geom), 2 ) < 0.001
;*/

/* –––––––––––––––––––––––– */

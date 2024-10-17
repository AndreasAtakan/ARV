
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Kommuneplanen finnes i tabell ved navn "__kommuneplan"
	- Kommunedelplanen finnes i tabell ved navn "__kommunedelplan"
	- Reguleringsplanen finnes i tabell ved navn "__reguleringsplan"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/

drop table if exists "__planer";

create table if not exists "__kommuneplan" (
	"OBJECTID" bigint,
	objtype text,
	planidentifikasjon text,
	lokalid text,
	versjonid text,
	arealbruk text,
	arealformal text,
	arealbruksstatus text,
	eierform text,
	ikrafttredelsesdato timestamp with time zone,
	plannavn text,
	plantype text,
	planstatus text,
	planbestemmelse text,
	lovreferanse text,
	kommunenummer text,
	geom Geometry(MultiPolygon, 25833)
);
create table if not exists "__kommunedelplan" (
	"OBJECTID" bigint,
	objtype text,
	planidentifikasjon text,
	lokalid text,
	versjonid text,
	arealbruk text,
	arealformal text,
	arealbruksstatus text,
	eierform text,
	ikrafttredelsesdato timestamp with time zone,
	plannavn text,
	plantype text,
	planstatus text,
	planbestemmelse text,
	lovreferanse text,
	kommunenummer text,
	geom Geometry(MultiPolygon, 25833)
);
create table if not exists "__reguleringsplan" (
	"OBJECTID" bigint,
	objtype text,
	planidentifikasjon text,
	lokalid text,
	versjonid text,
	reguleringsformal text,
	arealformal text,
	arealbruksstatus text,
	eierform text,
	ikrafttredelsesdato timestamp with time zone,
	plannavn text,
	plantype text,
	planstatus text,
	planbestemmelse text,
	lovreferanse text,
	kommunenummer text,
	geom Geometry(MultiPolygon, 25833)
);

/* Legg til formålskolonner for å sikre mot "kolonne finnes ikke" feil */
alter table "__kommuneplan" add column if not exists arealformal varchar(200);
alter table "__kommuneplan" add column if not exists arealbruk varchar(200)
;

/* Last inn alle områder fra kommuneplan */
create table "__planer"
as
select
	"OBJECTID"::bigint as id,
	objtype,
	planidentifikasjon,
	'Kommuneplan' as plankilde,
	NOW() as planreservedato,
	lokalid,
	versjonid,
	eierform::text,
	koder.kode as arealformaal,
	arealbruksstatus::text as arealstatus,
	ikrafttredelsesdato::timestamp with time zone,
	plannavn,
	plantype::text,
	planstatus::text,
	planbestemmelse::text,
	lovreferanse::text,
	kommunenummer,
	ST_Area( geom ) as planlagt_m2,
	geom
from
	"__kommuneplan" as plan,
	arealformaalskoder as koder
where
	koder.kilde = 'Kommuneplan' and
	CONCAT(arealformal, arealbruk) = koder.orig_kode and
	not koder.exclude and
	(
		not ST_IsEmpty(geom) or
		geom is not null
	)
;

/* Legg inn romlige indekser */
create index if not exists "planer_geom_geom_idx" on "public"."__planer" using GIST ("geom");
create index if not exists "delplan_geom_geom_idx" on "public"."__kommunedelplan" using GIST ("geom");
create index if not exists "regplan_geom_geom_idx" on "public"."__reguleringsplan" using GIST ("geom");


/* Fjern overlappende deler av områder mellom kommuneplan og kommunedelplan
	slik at nyeste plan vises */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, kommunedelplan.geom ), 3 ) )
from
	(
		select ST_Union(kommunedelplan.geom) as geom
		from
			"__planer" as plan,
			"__kommunedelplan" as kommunedelplan
		where
			kommunedelplan.ikrafttredelsesdato::timestamp with time zone
			>=
			plan.ikrafttredelsesdato
	) as kommunedelplan
where ST_Intersects( plan.geom, kommunedelplan.geom )
;
update "__kommunedelplan" as kommunedelplan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( kommunedelplan.geom, plan.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan,
			"__kommunedelplan" as kommunedelplan
		where
			kommunedelplan.ikrafttredelsesdato::timestamp with time zone
			<
			plan.ikrafttredelsesdato
	) as plan
where ST_Intersects( plan.geom, kommunedelplan.geom )
;


/* Legg til formålskolonner på samme måte for kommuneplan */
alter table "__kommunedelplan" add column if not exists arealformal varchar(200);
alter table "__kommunedelplan" add column if not exists arealbruk varchar(200);
;

/* Last inn alle områder fra kommunedelplan */
insert into "__planer"
select
	"OBJECTID"::bigint as id,
	objtype,
	planidentifikasjon,
	'Kommuneplan (delplan)' as plankilde,
	NOW() as planreservedato,
	lokalid,
	versjonid,
	eierform::text,
	koder.kode as arealformaal,
	arealbruksstatus::text as arealstatus,
	ikrafttredelsesdato::timestamp with time zone,
	plannavn,
	plantype::text,
	planstatus::text,
	planbestemmelse::text,
	lovreferanse::text,
	kommunenummer,
	ST_Area( geom ) as planlagt_m2,
	geom
from
	"__kommunedelplan" as kommunedelplan,
	arealformaalskoder as koder
where
	koder.kilde = 'Kommuneplan' and
	CONCAT(arealformal, arealbruk) = koder.orig_kode and
	not koder.exclude and
	(
		not ST_IsEmpty(geom) or
		geom is not null
	)
;



/* Fjern overlappende deler av områder mellom kommune/-delplan og reguleringsplan
	slik at nyeste plan vises */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, reguleringsplan.geom ), 3 ) )
from
	(
		select ST_Union(reguleringsplan.geom) as geom
		from
			"__planer" as plan,
			"__reguleringsplan" as reguleringsplan
		where
			reguleringsplan.ikrafttredelsesdato::timestamp with time zone
			>=
			plan.ikrafttredelsesdato
	) as reguleringsplan
where ST_Intersects( plan.geom, reguleringsplan.geom )
;
update "__reguleringsplan" as reguleringsplan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( reguleringsplan.geom, plan.geom ), 3 ) )
from
	(
		select ST_Union(plan.geom) as geom
		from
			"__planer" as plan,
			"__reguleringsplan" as reguleringsplan
		where
			reguleringsplan.ikrafttredelsesdato::timestamp with time zone
			<
			plan.ikrafttredelsesdato
	) as plan
where ST_Intersects( plan.geom, reguleringsplan.geom )
;


/* Legg til formålskolonner på samme måte for kommunedelplan */
alter table "__reguleringsplan" add column if not exists arealformal varchar(200);
alter table "__reguleringsplan" add column if not exists reguleringsformal varchar(200);


/* Last inn alle områder fra reguleringsplan */
insert into "__planer"
select
	"OBJECTID"::bigint as id,
	objtype,
	planidentifikasjon,
	'Reguleringsplan' as plankilde,
	NOW() as planreservedato,
	lokalid,
	versjonid,
	eierform::text,
	koder.kode as arealformaal,
	'1' as arealstatus,
	ikrafttredelsesdato::timestamp with time zone,
	plannavn,
	plantype::text,
	planstatus::text,
	planbestemmelse::text,
	lovreferanse::text,
	kommunenummer,
	ST_Area( geom ) as planlagt_m2,
	geom
from
	"__reguleringsplan" as reguleringsplan,
	arealformaalskoder as koder
where
	koder.kilde = 'Reguleringsplan' and
	CONCAT(reguleringsformal, arealformal) = koder.orig_kode and
	not koder.exclude and
	(
		not ST_IsEmpty(geom) or
		geom is not null
	)
;



/* Legg til formålsbeskrivelse, arealformålsgruppe og arealklasse */
alter table "__planer" add column arealformaalsbeskrivelse varchar(200);
alter table "__planer" add column arealformaalsgruppe varchar(200);
alter table "__planer" add column arealklasse varchar(200)
;

update "__planer" as plan
set
	arealformaalsbeskrivelse = koder.beskrivelse,
	arealformaalsgruppe = koder.gruppe,
	arealklasse = koder.klasse
from arealformaalskoder as koder
where
	plan.plankilde like koder.kilde || '%' and
	plan.arealformaal = koder.kode
;

/* Legg til kommune og fylke info */
alter table "__planer" add column kommune varchar(200);
alter table "__planer" add column fylkesnummer varchar(10);
alter table "__planer" add column fylke varchar(200)
;

update "__planer" as plan
set
	kommune = kommuner.kommune,
	fylkesnummer = kommuner.fylkesnummer,
	fylke = kommuner.fylke
from kommuner
where ST_Intersects( plan.geom, kommuner.geom )
;

/* Legg til planalder info */
alter table "__planer" add column planalder varchar(50);

update "__planer"
set planalder = 'Manglende planalder'
where
	ikrafttredelsesdato is null or
	extract(year from ikrafttredelsesdato) = 1800 and
	extract(month from ikrafttredelsesdato) = 1 and
	extract(day from ikrafttredelsesdato) = 1
;
update "__planer"
set planalder = 'Eldre enn 1985'
where
	planalder is null and
	extract(year from ikrafttredelsesdato) < 1985
;
update "__planer"
set planalder = 'Eldre enn 2008'
where
	planalder is null and
	extract(year from ikrafttredelsesdato) < 2008
;
update "__planer"
set planalder = 'Nyere enn 2008'
where
	planalder is null and
	extract(year from ikrafttredelsesdato) >= 2008
;

/* –––––––––––––––––––––––– */

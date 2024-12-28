
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

create table if not exists "__planer" (
	"OBJECTID" bigint,
	objtype text,
	planidentifikasjon text,
	planlagt_status text,
	plankilde text,
	planreservedato timestamp with time zone,
	lokalid text,
	versjonid text,
	eierform text,
	arealformaal text,
	arealstatus text,
	ikrafttredelsesdato timestamp with time zone,
	plannavn text,
	plantype text,
	planstatus text,
	planbestemmelse text,
	lovreferanse text,
	kommunenummer text,
	planlagt_m2 double precision,
	geom Geometry(MultiPolygon, 25833)
);

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
	arealbruksstatus integer,
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
	arealbruksstatus integer,
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
insert into "__planer"
select
	"OBJECTID"::bigint as id,
	objtype,
	planidentifikasjon,
	'Fremtidig' as planlagt_status,
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
where
	plan.planlagt_status = 'Fremtidig' and
	ST_Intersects( plan.geom, kommunedelplan.geom )
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
			plan.planlagt_status = 'Fremtidig' and
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
	'Fremtidig' as planlagt_status,
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
	(
		not ST_IsEmpty(geom) or
		geom is not null
	)
;





/* Fjern alle overlappende områder fra kommuneplan
   mellom reguleringsplan og kommuneplan */
update "__planer" as plan
set geom = ST_Multi( ST_CollectionExtract( ST_Difference( plan.geom, reg.geom ), 3 ) )
from
	( select ST_Union(geom) as geom from "__reguleringsplan" ) as reg
where
	plan.planlagt_status = 'Fremtidig' and
	ST_Intersects( plan.geom, reg.geom )
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
	'Fremtidig' as planlagt_status,
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
	(
		not ST_IsEmpty(geom) or
		geom is not null
	)
;





/* Legg til formålsbeskrivelse, arealformålsgruppe og arealklasse */
alter table "__planer" add column if not exists arealformaalsbeskrivelse varchar(200);
alter table "__planer" add column if not exists arealformaalsgruppe varchar(200);
alter table "__planer" add column if not exists arealklasse varchar(200)
;

update "__planer" as plan
set
	arealformaalsbeskrivelse = koder.beskrivelse,
	arealformaalsgruppe = koder.gruppe,
	arealklasse = koder.klasse
from arealformaalskoder as koder
where
	plan.planlagt_status = 'Fremtidig' and
	plan.plankilde like koder.kilde || '%' and
	plan.arealformaal = koder.kode
;





/* Legg til kommune og fylke info */
alter table "__planer" add column if not exists kommune varchar(200);
alter table "__planer" add column if not exists fylkesnummer varchar(10);
alter table "__planer" add column if not exists fylke varchar(200)
;

update "__planer" as plan
set
	kommune = kommuner.kommune,
	fylkesnummer = kommuner.fylkesnummer,
	fylke = kommuner.fylke
from kommuner
where
	plan.planlagt_status = 'Fremtidig' and
	ST_Intersects( plan.geom, kommuner.geom )
;





/* Legg til planalder info */
alter table "__planer" add column if not exists planalder varchar(50);

update "__planer"
set planalder = 'Manglende planalder'
where
	planlagt_status = 'Fremtidig' and
	ikrafttredelsesdato is null or
	extract(year from ikrafttredelsesdato) = 1800 and
	extract(month from ikrafttredelsesdato) = 1 and
	extract(day from ikrafttredelsesdato) = 1
;
update "__planer"
set planalder = 'Eldre enn 1985'
where
	planlagt_status = 'Fremtidig' and
	planalder is null and
	extract(year from ikrafttredelsesdato) < 1985
;
update "__planer"
set planalder = 'Eldre enn 2008'
where
	planlagt_status = 'Fremtidig' and
	planalder is null and
	extract(year from ikrafttredelsesdato) < 2008
;
update "__planer"
set planalder = 'Nyere enn 2008'
where
	planlagt_status = 'Fremtidig' and
	planalder is null and
	extract(year from ikrafttredelsesdato) >= 2008
;





/* Oversette kode felt */
update "__planer" as plan
set arealstatus = felt.verdi
from
	(
		select '1' as kode, 'Nåværende' as verdi union
		select '2', 'Framtidig' union
		select '3', 'Videreutvikling av nåværende'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.arealstatus = felt.kode
;

update "__planer" as plan
set eierform = felt.verdi
from
	(
		select '1' as kode, 'Offentlig formål' as verdi union
		select '2', 'Felles' union
		select '3', 'Annen eierform'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.eierform = felt.kode
;

update "__planer" as plan
set plantype = felt.verdi
from
	(
		select '20' as kode, 'Kommuneplanens arealdel' as verdi union
		select '21', 'Kommunedelplan' union
		select '22', 'Mindre endring av kommune(del)plan'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.plantype = felt.kode
;

update "__planer" as plan
set planstatus = felt.verdi
from
	(
		select '1' as kode, 'Planlegging igangsatt' as verdi union
		select '2', 'Planforslag' union
		select '3', 'Endelig vedtatt arealplan'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.planstatus = felt.kode
;

update "__planer" as plan
set planbestemmelse = felt.verdi
from
	(
		select '1' as kode, 'Med bestemmelser som egen tekst' as verdi union
		select '2', 'Uten bestemmelser' union
		select '3', 'Planbestemmelser fremgår kun av kartet' union
		select '4', 'Planbestemmelser både kart og tekst'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.planbestemmelse = felt.kode
;

update "__planer" as plan
set lovreferanse = felt.verdi
from
	(
		select '1' as kode, 'Før BL 1924' as verdi union
		select '2', 'BL 1924' union
		select '3', 'BL 1965' union
		select '4', 'PBL 1985' union
		select '5', 'PBL 1985 eller før' union
		select '6', 'PBL 2008'
	) as felt
where
	plan.planlagt_status = 'Fremtidig' and
	plan.lovreferanse = felt.kode
;

/* –––––––––––––––––––––––– */

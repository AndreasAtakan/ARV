
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Kommuneplanen finnes i tabell ved navn "__kommuneplan_gjeldende"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/

drop table if exists "__planer";

create table if not exists "__kommuneplan_gjeldende" (
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

/* Legg til formålskolonner for å sikre mot "kolonne finnes ikke" feil */
alter table "__kommuneplan_gjeldende" add column if not exists arealformal varchar(200);
alter table "__kommuneplan_gjeldende" add column if not exists arealbruk varchar(200)
;

/* Last inn alle områder fra kommuneplan */
create table "__planer"
as
select
	"OBJECTID"::bigint as id,
	objtype,
	planidentifikasjon,
	'Gjeldende' as planlagt_status,
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
	"__kommuneplan_gjeldende" as plan,
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
	plan.planlagt_status = 'Gjeldende' and
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
	plan.planlagt_status = 'Gjeldende' and
	ST_Intersects( plan.geom, kommuner.geom )
;




/* Legg til planalder info */
alter table "__planer" add column if not exists planalder varchar(50);

update "__planer"
set planalder = 'Manglende planalder'
where
	planlagt_status = 'Gjeldende' and
	ikrafttredelsesdato is null or
	extract(year from ikrafttredelsesdato) = 1800 and
	extract(month from ikrafttredelsesdato) = 1 and
	extract(day from ikrafttredelsesdato) = 1
;
update "__planer"
set planalder = 'Eldre enn 1985'
where
	planlagt_status = 'Gjeldende' and
	planalder is null and
	extract(year from ikrafttredelsesdato) < 1985
;
update "__planer"
set planalder = 'Eldre enn 2008'
where
	planlagt_status = 'Gjeldende' and
	planalder is null and
	extract(year from ikrafttredelsesdato) < 2008
;
update "__planer"
set planalder = 'Nyere enn 2008'
where
	planlagt_status = 'Gjeldende' and
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
	plan.planlagt_status = 'Gjeldende' and
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
	plan.planlagt_status = 'Gjeldende' and
	plan.eierform = felt.kode
;

update "__planer" as plan
set plantype = felt.verdi
from
	(
		select '4' as kode, 'Statlig arealplan' as verdi union
		select '10', 'Fylkesplanens arealdel' union
		select '11', 'Fylkesdelplan' union
		select '12', 'Regionalplan' union
		select '20', 'Kommuneplanens arealdel' union
		select '21', 'Kommunedelplan' union
		select '22', 'Mindre endring av kommune(del)plan' union
		select '30', 'Eldre reguleringsplan' union
		select '31', 'Mindre reguleringsendring' union
		select '32', 'Bebyggelsesplan ihht. Reguleringsplan' union
		select '33', 'Bebyggelsesplan ihht kommunepl. arealdel' union
		select '34', 'Områderegulering' union
		select '35', 'Detaljregulering' union
	) as felt
where
	plan.planlagt_status = 'Gjeldende' and
	plan.plantype = felt.kode
;

update "__planer" as plan
set planstatus = felt.verdi
from
	(
		select '1' as kode, 'Planlegging igangsatt' as verdi union
		select '2', 'Planforslag' union
		select '3', 'Endelig vedtatt arealplan' union
		select '4', 'Opphevet' union
		select '5', 'Utgått/erstattet' union
		select '6', 'Vedtatt plan med utsatt rettsvirkning' union
		select '8', 'Overstyrt'
	) as felt
where
	plan.planlagt_status = 'Gjeldende' and
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
	plan.planlagt_status = 'Gjeldende' and
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
	plan.planlagt_status = 'Gjeldende' and
	plan.lovreferanse = felt.kode
;

/* –––––––––––––––––––––––– */

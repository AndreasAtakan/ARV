
/*   Planmosaikk   */
/* –––––––––––––––––––––––– */
/*
	Antakelser når dette skriptet kjører

	Input tabeller:
	- Alle planene finnes i tabell ved navn "__plan"

	Output tabell:
	- Planenes sammensatte mosaikk vil finnes i tabell ved navn "__planer"
*/


delete from "__planer" where ST_IsEmpty(geom) or geom is null;
alter table "__planer" drop column regplan_pri;
alter table "__planer" add column if not exists planlagt_m2 double precision;
update "__planer" set planlagt_m2 = ST_Area( geom );





/* Legg til formålsbeskrivelse, arealformålsgruppe og arealklasse */
alter table "__planer" alter column arealformaal type varchar(4) using arealformaal::varchar(4);
alter table "__planer" add column if not exists arealformaalsbeskrivelse varchar(200);
alter table "__planer" add column if not exists arealformaalsgruppe varchar(200);
alter table "__planer" add column if not exists arealklasse varchar(200)
;
update "__planer" as plan
set
	arealformaal = koder.kode,
	arealformaalsbeskrivelse = koder.beskrivelse,
	arealformaalsgruppe = koder.gruppe,
	arealklasse = koder.klasse
from arealformaalskoder as koder
where
	plan.plankilde like koder.kilde || '%' and
	plan.arealformaal = koder.orig_kode
;





/* Legg til kommune og fylke info */
alter table "__planer" add column if not exists kommunenummer varchar(10);
alter table "__planer" add column if not exists kommune varchar(200);
alter table "__planer" add column if not exists fylkesnummer varchar(10);
alter table "__planer" add column if not exists fylke varchar(200)
;

update "__planer" as plan
set
	kommunenummer = kommuner.kommunenummer,
	kommune = kommuner.kommune,
	fylkesnummer = kommuner.fylkesnummer,
	fylke = kommuner.fylke
from kommuner
where ST_Intersects( plan.geom, kommuner.geom )
;





/* Legg til planalder info */
alter table "__planer" add column if not exists planalder varchar(50);

update "__planer"
set planalder = 'Manglende planalder'
where
	ikraft_dato is null or
	extract(year from ikraft_dato) = 1800 and
	extract(month from ikraft_dato) = 1 and
	extract(day from ikraft_dato) = 1
;
update "__planer"
set planalder = 'Eldre enn 1985'
where
	planalder is null and
	extract(year from ikraft_dato) < 1985
;
update "__planer"
set planalder = 'Eldre enn 2008'
where
	planalder is null and
	extract(year from ikraft_dato) < 2008
;
update "__planer"
set planalder = 'Nyere enn 2008'
where
	planalder is null and
	extract(year from ikraft_dato) >= 2008
;





/* Oversette kode felt */
alter table "__planer" alter column arealstatus type text using arealstatus::text;
alter table "__planer" alter column eierform type text using eierform::text;
alter table "__planer" alter column plantype type text using plantype::text;
alter table "__planer" alter column planstatus type text using planstatus::text;
alter table "__planer" alter column planbestemmelse type text using planbestemmelse::text;
alter table "__planer" alter column lovreferanse type text using lovreferanse::text;

update "__planer" as plan
set arealstatus = felt.verdi
from
	(
		select '1' as kode, 'Nåværende' as verdi union
		select '2', 'Framtidig' union
		select '3', 'Videreutvikling av nåværende'
	) as felt
where plan.arealstatus = felt.kode
;

update "__planer" as plan
set eierform = felt.verdi
from
	(
		select '1' as kode, 'Offentlig formål' as verdi union
		select '2', 'Felles' union
		select '3', 'Annen eierform'
	) as felt
where plan.eierform = felt.kode
;

update "__planer" as plan
set plantype = felt.verdi
from
	(
		select '20' as kode, 'Kommuneplanens arealdel' as verdi union
		select '21', 'Kommunedelplan' union
		select '22', 'Mindre endring av kommune(del)plan'
	) as felt
where plan.plantype = felt.kode
;

update "__planer" as plan
set planstatus = felt.verdi
from
	(
		select '1' as kode, 'Planlegging igangsatt' as verdi union
		select '2', 'Planforslag' union
		select '3', 'Endelig vedtatt arealplan'
	) as felt
where plan.planstatus = felt.kode
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
where plan.planbestemmelse = felt.kode
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
where plan.lovreferanse = felt.kode
;

/* –––––––––––––––––––––––– */


create or replace procedure omraade_overlapp(type text, overlapp_table text)
language plpgsql
as $$
begin

	create table if not exists "__overlapp" (
		planomraade_id varchar(50),
		planlegging_status varchar(50),
		formaal varchar(200),
		formaalsgruppe varchar(200),
		overlapp_type varchar(200),
		kommunenummer varchar(10),
		kommune varchar(200),
		fylkesnummer varchar(10),
		fylke varchar(200),
		planalder varchar(50),
		areal_m2 double precision,
		geom Geometry(MultiPolygon, 25833)
	);

	execute format('
		insert into "__overlapp"
		select
			plan.lokalid as planomraade_id,
			plan.planlegging_status,
			plan.arealformaalsbeskrivelse as formaal,
			plan.arealformaalsgruppe as formaalsgruppe,
			''%s'' as overlapp_type,
			plan.kommunenummer,
			plan.kommune,
			plan.fylkesnummer,
			plan.fylke,
			plan.planalder,
			ST_Area( ST_CollectionExtract( ST_Intersection( plan.geom, overlapp.geom ), 3 ) ) as areal_m2,
			ST_Multi( ST_CollectionExtract( ST_Intersection( plan.geom, overlapp.geom ), 3 ) ) as geom
		from
			"__planer" as plan,
			%s as overlapp
		where
			ST_Intersects( plan.geom, overlapp.geom )
		;
	', type, overlapp_table);

	/* Slett områder med tom geometri */
	delete from "__overlapp" where areal_m2 <= 0 or ST_IsEmpty(geom) or geom is null;

	/* Legg til overlapp-areal felt i __planer */
	execute format('
		alter table "__planer" add column if not exists %s_m2 double precision default 0;

		update "__planer" as p
		set %s_m2 = o.sum
		from
			(
				select
					o.planomraade_id,
					SUM(o.areal_m2) as sum
				from
					"__planer" as p,
					"__overlapp" as o
				where
					o.planomraade_id = p.lokalid and
					o.overlapp_type = ''%s''
				group by o.planomraade_id
			) as o
		where o.planomraade_id = p.lokalid
		;',
		replace(replace(type, ' ', '_'), 'å', 'aa'),
		replace(replace(type, ' ', '_'), 'å', 'aa'),
		type
	);

	commit;

end;$$

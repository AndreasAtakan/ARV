import json
import os
import psycopg2
from psycopg2 import sql

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')

def lambda_handler(event, context):
	if 'id' not in event or \
	   'user_id' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }

	_id = event['id']
	user_id = event['user_id']
	# IMPLEMENT; check if user has access to account

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		cur = conn.cursor()

		cur.execute('SELECT plandata, overlapp FROM arv.\"Accounts\" WHERE id = %s', (_id))
		row = cur.fetchone()
		pd = row[0]; ol = row[1]
		cur.execute( sql.SQL('SELECT fylkesnummer, kommunenummer FROM arv.{} LIMIT 1').format(sql.Identifier(pd)) )
		row = cur.fetchone()
		fnr = row[0]; knr = row[1]

		q = sql.SQL(
			'''SELECT
				json_build_object(
					'type', 'FeatureCollection',
					'features', json_agg(ST_AsGeoJSON(T.*)::json)
				) AS g
			FROM arv.{} AS T'''
		).format(sql.Identifier(pd))
		cur.execute(q)
		row = cur.fetchone()
		RES['plandata'] = row[0]

		q = sql.SQL(
			'''SELECT
				json_build_object(
					'type', 'FeatureCollection',
					'features', json_agg(ST_AsGeoJSON(T.*)::json)
				) AS g
			FROM arv.{} AS T'''
		).format(sql.Identifier(ol))
		cur.execute(q)
		row = cur.fetchone()
		RES['overlapp'] = row[0]

		if True: # if arealregnskapet er dekket i grunnkart_for_arealregnskap
			q = sql.SQL(
				'''SELECT
					json_build_object(
						'type', 'FeatureCollection',
						'features', json_agg(
							json_build_object(
								'type', 'Feature',
								'geometry', ST_AsGeoJSON(ST_Transform(T.geom, 4326))::json,
								'properties', json_build_object(
									'adekk', T.adekk,
									'arealdekke', T.arealdekke,
									'okosys', T.okosys,
									'okosystemtype_niva2', T.okosystemtype_niva2
								)
							)
						)
					) AS g
				FROM
					grunnkart_arealregnskap.{knr} AS T,
					( SELECT ST_Buffer(ST_Transform(ST_SetSRID(ST_Extent(geom), 4326), 25833), 10) AS geom FROM arv.{plandata} ) AS BBOX
				WHERE ST_Intersects(T.geom, BBOX.geom)'''
			).format( knr=knr, plandata=sql.Identifier(pd) )
			cur.execute(q)
			row = cur.fetchone()
			RES['natur'] = row[0]

		if True: # if eier organisasjon er en kommune
			q = sql.SQL(
				'''SELECT
					json_build_object(
						'type', 'FeatureCollection',
						'features', json_agg(ST_AsGeoJSON(T.*)::json)
					) AS g
				FROM
					(
						SELECT ST_Transform(K.geom, 4326) AS geom
						FROM
							kommuner AS K,
							(SELECT fylkesnummer, kommunenummer FROM arv.{} LIMIT 1) AS P
						WHERE
							K.fylkesnummer = P.fylkesnummer AND
							K.kommunenummer = P.kommunenummer
					) AS T'''
			).format(sql.Identifier(pd))
			cur.execute(q)
			row = cur.fetchone()
			RES['kommunegrense'] = row[0]

		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES) }

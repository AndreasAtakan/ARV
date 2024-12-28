import json
from os import environ
import psycopg2
from psycopg2 import sql

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')

def lambda_handler(event, context):
	if 'id' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }

	_id = event['id']
	#user_id = event.get('user_id', None)  # Replace with logic to extract the authenticated user ID

	#if not is_user_accounts_valid(user_id, _id):
	#	return {
	#		'statusCode': 401,
	#		'body': json.dumps({'error': 'Unauthorized'})
	#	}

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(
			host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD,
			dbname=environ['DB_NAME'],
			user=environ['DB_USER'],
			password=environ['DB_PASSWORD'],
			host=environ['DB_HOST'],
			port=environ['DB_PORT']
		)
		conn.autocommit = True
		cur = conn.cursor()

		cur.execute('SELECT plandata, overlapp FROM arv.\"Accounts\" WHERE id = %s', (_id))
		row = cur.fetchone()

		if not row: return { 'statusCode': 422, 'body': json.dumps({'error': 'No accounts found'}) }
		plandata, overlapp = row

		cur.execute(
			sql.SQL('DROP TABLE IF EXISTS arv.{}, arv.{}')
			.format(sql.Identifier(plandata), sql.Identifier(overlapp))
		)

		cur.execute('DELETE FROM arv.\"Accounts\" WHERE id = %s', (_id))

		RES = {'status': 'success'}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES) }

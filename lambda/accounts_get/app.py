import json
import os
import psycopg2
from psycopg2 import sql

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')

def lambda_handler(event, context):
	if 'user_id' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }

	user_id = event['user_id']
	# IMPLEMENT
	org_id = getOrgID(user_id)

	#if not is_user_accounts_valid(user_id, _id):
	#	return {
	#		'statusCode': 401,
	#		'body': json.dumps({'error': 'Unauthorized'})
	#	}

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		cur = conn.cursor()

		cur.execute('SELECT id, title, description, thumbnail, created_date FROM arv.\"Accounts\" WHERE organization_id = %s', (org_id))
		RES = cur.fetchall()
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES) }

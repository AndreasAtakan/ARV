import json
import os
import psycopg2
from psycopg2 import sql
from common.common import get_org

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')

def lambda_handler(event, context):
	if 'username' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }
	username = event['username']

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		cur = conn.cursor()

		org_id = get_org(username, cur)
		if not org_id: return { 'statusCode': 401, 'body': json.dumps({'error': 'Unauthorized'}) }

		cur.execute('SELECT filename FROM arv.\"Planfiler\" WHERE org_id = %s', (org_id,))
		RES = cur.fetchall()
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES, default=str) }

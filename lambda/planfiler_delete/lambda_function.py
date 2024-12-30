import json
import os
import psycopg2
from psycopg2 import sql
import boto3
from common.common import get_org, user_planfil_access

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
BUCKET_NAME = os.getenv('BUCKET_NAME')

def lambda_handler(event, context):
	if 'username' not in event or \
	   'filename' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }
	username = event['username']
	filename = event['filename']

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		conn.autocommit = True
		cur = conn.cursor()

		org_id = get_org(username, cur)
		if not user_planfil_access(username, filename, cur) or \
		   not org_id: return { 'statusCode': 401, 'body': json.dumps({'error': 'Unauthorized'}) }

		s3_client = boto3.client('s3')
		s3_client.delete_object(Bucket=BUCKET_NAME, Key=filename)

		cur.execute('DELETE FROM arv.\"Planfiler\" WHERE filename = %s', (filename,))

		RES = {'status': 'success'}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES, default=str) }

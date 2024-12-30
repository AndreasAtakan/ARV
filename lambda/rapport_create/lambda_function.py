import json
import uuid
import os
import psycopg2
from psycopg2 import sql
import boto3
from common.common import get_org

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
BUCKET_NAME = os.getenv('BUCKET_NAME')

def lambda_handler(event, context):
	if 'id' not in event or \
	   'username' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }
	_id = event['id']
	username = event['username']
	report_type = event.get('type', 'PDF')

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		cur = conn.cursor()

		org_id = get_org(username, cur)
		if not org_id: return { 'statusCode': 401, 'body': json.dumps({'error': 'Unauthorized'}) }

		# TODO; based on the data in "plandata_" and "overlapp_" tables, create a report
		#s3_client = boto3.client('s3')
		#body = base64.b64decode(event[fil.key].content)
		#key = f'{uuid.uuid4()}-{fil.name}'
		#s3_client.put_object(Bucket=BUCKET_NAME, Key=key, Body=body)		
		#
		#cur.execute('INSERT INTO arv.\"Planfiler\" (org_id,filename) VALUES (%s,%s)', (org_id,key))

		RES = {'status': 'success'}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES, default=str) }

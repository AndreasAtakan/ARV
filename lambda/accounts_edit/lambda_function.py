import json
import uuid
import os
import base64
import psycopg2
from psycopg2 import sql
import boto3
from common.common import parse_multipart, user_account_access

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
BUCKET_NAME = os.getenv('BUCKET_NAME')

MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp']

def lambda_handler(event, context):
	event = parse_multipart(event['body'], event['headers']['Content-Type'])
	if 'id' not in event or \
	   'username' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }
	_id = event['id']
	username = event['username']
	title = event.get('title', None)
	description = event.get('description', None)
	thumbnail = event.get('thumbnail', None)

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		conn.autocommit = True
		cur = conn.cursor()

		if not user_account_access(username _id, cur): return { 'statusCode': 401, 'body': json.dumps({'error': 'Unauthorized'}) }

		if title is not None:
			cur.execute('UPDATE arv.\"Accounts\" SET title = %s WHERE id = %s', (title,_id))
		if description is not None:
			cur.execute('UPDATE arv.\"Accounts\" SET description = %s WHERE id = %s', (description,_id))

		s3_client = boto3.client('s3')
		if thumbnail is not None:
			if thumbnail.type.lower() not in MIME_TYPES: raise ValueError('File type not allowed.')
			fil = base64.b64decode(event[thumbnail.key].content)
			key = f'{uuid.uuid4()}-{thumbnail.name}'
			s3_client.put_object(Bucket=BUCKET_NAME, Key=key, Body=fil)
			upload = f'https://{BUCKET_NAME}.s3.amazonaws.com/{key}'
			cur.execute('UPDATE arv.\"Accounts\" SET thumbnail = %s WHERE id = %s', (upload,_id))

		RES = {'status': 'success'}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES, default=str) }

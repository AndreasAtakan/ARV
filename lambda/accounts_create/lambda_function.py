import json
import uuid
import os
import base64
import psycopg2
from psycopg2 import sql
import boto3
from common.common import parse_multipart, get_org

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
BUCKET_NAME = os.getenv('BUCKET_NAME')

MIME_TYPES = ['application/zip', 'application/gml+xml', 'application/geopackage+sqlite3', 'application/octet-stream']

def lambda_handler(event, context):
	event = parse_multipart(event['body'], event['headers']['Content-Type'])
	if 'username' not in event or \
	   'title' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }
	username = event['username']
	title = event['title']
	description = event.get('description', None)
	planer = event.get('planer', None) # tekstfelt med liste av planfiner som allerede finnes i S3
	planfiler = json.loads(event.get('planfiler', '[]')) # metadata som beskriver alle binære planfiler som skal brukes og må lastes opp til S3

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		conn.autocommit = True
		cur = conn.cursor()

		org_id = get_org(username, cur)
		if not org_id: return { 'statusCode': 401, 'body': json.dumps({'error': 'Unauthorized'}) }

		# planfiler struktur; [ { name, type, key; 'fil_$i' } ]
		# event['fil_$i'] er filens binære data

		s3_client = boto3.client('s3')
		if planfiler:
			for fil in planfiler:
				if fil.type.lower() not in MIME_TYPES: raise ValueError('File type not allowed.')
				body = base64.b64decode(event[fil.key].content)
				key = f'{uuid.uuid4()}-{fil.name}'
				s3_client.put_object(Bucket=BUCKET_NAME, Key=key, Body=body)

		# TODO

		RES = {'id': _id}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES, default=str) }

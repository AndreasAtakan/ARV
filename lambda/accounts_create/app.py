import json
import os
import base64
import psycopg2
from psycopg2 import sql

DB_HOST = os.getenv('DB_HOST')
DB_NAME = os.getenv('DB_NAME')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
BUCKET_NAME = ''

MIME_TYPES = ['application/zip', 'application/gml+xml', 'application/geopackage+sqlite3', 'application/octet-stream']

def lambda_handler(event, context):
	event = parse_multipart(event['body'], event['headers']['Content-Type'])
	if 'user_id' not in event or \
	   'title' not in event: return { 'statusCode': 422, 'body': json.dumps({'error': 'Missing field in request'}) }

	user_id = event['user_id']
	title = event['title']
	description = event.get('description', None)
	planer = event.get('planer', None) # tekstfelt med liste av planfiner som allerede finnes i S3
	planfiler = json.loads(event.get('planfiler', '[]')) # metadata som beskriver alle binære planfiler som skal brukes og må lastes opp til S3

	RES = {}
	STATUS_CODE = 0

	try:
		conn = psycopg2.connect(host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASSWORD)
		cur = conn.cursor()

		# planfiler struktur; [ { name, type, key; 'fil_$i' } ]
		# event['fil_$i'] er filens binære data

		s3_client = boto3.client('s3')
		if planfiler:
			for fil in planfiler:
				if fil.type.lower() not in MIME_TYPES: raise ValueError('File type not allowed.')
				body = base64.b64decode(event[fil.key].content)
				s3_client.put_object(Bucket=BUCKET_NAME, Key=fil.name, Body=body)

		# TODO

		RES = {'id': _id}
		STATUS_CODE = 200

	except Exception as e:
		print(f'Error: {e}')
		RES = {'error': 'Internal Server Error'}
		STATUS_CODE = 500

	finally:
		cur.close(); conn.close()
		return { 'statusCode': STATUS_CODE, 'body': json.dumps(RES) }

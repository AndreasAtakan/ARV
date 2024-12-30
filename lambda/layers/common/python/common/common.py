import cgi
from io import BytesIO
#import boto3
import psycopg2
from psycopg2 import sql

def parse_multipart(body, content_type):
	"""
	Parses a multipart/form-data request body into a dictionary.

	Args:
		body (bytes): The raw request body.
		content_type (str): The content type header from the request (e.g., "multipart/form-data; boundary=---XYZ").

	Returns:
		A dictionary with keys as field names and values as field values or file objects.
	"""
	# Extract boundary from content type
	boundary = content_type.split('boundary=')[-1]
	if not boundary:
		raise ValueError('Boundary not found in Content-Type header.')

	boundary = boundary.encode()  # Ensure boundary is in bytes for comparison

	# Parse the form data
	environ = {
		'REQUEST_METHOD': 'POST',
		'CONTENT_TYPE': content_type,
		'CONTENT_LENGTH': str(len(body)),
	}
	form = cgi.FieldStorage(fp=BytesIO(body), environ=environ, headers={"content-type": content_type})

	parsed_data = {}
	for key in form.keys():
		field_item = form[key]
		if field_item.filename:  # If the field is a file
			parsed_data[key] = {
				'filename': field_item.filename,
				'content': field_item.file.read()
			}
		else:  # Regular form field
			parsed_data[key] = field_item.value

	return parsed_data

# Example usage:
# raw_body = b'--boundary\r\nContent-Disposition: form-data; name="field1"\r\n\r\nvalue1\r\n--boundary--'
# content_type = "multipart/form-data; boundary=boundary"
# print(parse_multipart_form_data(raw_body, content_type))



def valid_user(username, cursor):
	try:
		cursor.execute('SELECT id FROM arv.\"User_Org\" WHERE username = %s', (username,))
		row = cursor.fetchone()
		return True if row else False
	except Exception as e: return False



def get_org(username, cursor):
	try:
		cursor.execute('SELECT org_id FROM arv.\"User_Org\" WHERE username = %s', (username,))
		row = cursor.fetchone()
		return row[0]
	except Exception as e: return None



def user_account_access(username, _id, cursor):
	try:
		cursor.execute('''
			SELECT U.org_id, A.organization_id
			FROM
				(SELECT org_id FROM arv.\"User_Org\" WHERE username = %s) AS U,
				(SELECT organization_id FROM arv.\"Accounts\" WHERE id = %s) AS A
		''', (username,_id))
		row = cursor.fetchone()
		return row[0] == row[1] or row[0] == '__master__'
	except Exception as e: return False



def account_kommune(_id, cursor):
	try:
		cursor.execute('''
			SELECT U.kommunenummer IS NOT NULL
			FROM
				arv.\"User_Org\" AS U INNER JOIN
				arv.\"Accounts\" AS A
					ON U.org_id = A.organization_id
			WHERE A.id = %s
		''', (_id,))
		row = cursor.fetchone()
		return row[0]
	except Exception as e: return False



def user_planfil_access(username, filename, cursor):
	try:
		cursor.execute('''
			SELECT U.org_id = P.organization_id
			FROM
				(SELECT org_id FROM arv.\"User_Org\" WHERE username = %s) AS U,
				(SELECT org_id FROM arv.\"Planfiler\" WHERE filename = %s) AS P
		''', (username,filename))
		row = cursor.fetchone()
		return row[0]
	except Exception as e: return False

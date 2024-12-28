import cgi
from io import BytesIO

def parse_multipart(body: bytes, content_type: str) -> dict:
	"""
	Parses a multipart/form-data request body into a dictionary.

	Args:
		body (bytes): The raw request body.
		content_type (str): The content type header from the request (e.g., "multipart/form-data; boundary=---XYZ").
	
	Returns:
		dict: A dictionary with keys as field names and values as field values or file objects.
	"""
	# Extract boundary from content type
	boundary = content_type.split("boundary=")[-1]
	if not boundary:
		raise ValueError("Boundary not found in Content-Type header.")

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

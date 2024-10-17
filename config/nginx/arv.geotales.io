server {
	listen 80;
	listen [::]:80;

	root /var/www/html/arv.geotales.io;

	# Add index.php to the list if you are using PHP
	index index.php index.html index.htm index.nginx-debian.html;

	server_name arv.geotales.io arv.nfedb.no ark.nfedb.no;

	# SSL configuration
	listen 443 ssl;
	listen [::]:443 ssl;

	ssl_certificate /etc/letsencrypt/live/arv.geotales.io/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/arv.geotales.io/privkey.pem;

	include /etc/letsencrypt/options-ssl-nginx.conf;

	#more_set_headers 'Access-Control-Allow-Origin: *';

	# Redirect non-https traffic to https
	if ($scheme != "https") {
		return 301 https://$host$request_uri;
	}

	location / {
		try_files $uri $uri/ =404;
	}

	# pass PHP scripts to FastCGI server
	location ~ \.php$ {
		if ($request_method = 'OPTIONS') {
			add_header 'Access-Control-Allow-Origin' '*';
			add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';

			# Custom headers and headers various browsers *should* be OK with but aren't
			add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range';

			# Tell client that this pre-flight info is valid for 20 days
			add_header 'Access-Control-Max-Age' 1728000;
			add_header 'Content-Type' 'text/plain; charset=utf-8';
			add_header 'Content-Length' 0;
			return 204;
		}
		if ($request_method = 'POST') {
			add_header 'Access-Control-Allow-Origin' '*' always;
			add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
			add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' always;
			add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range' always;
		}
		if ($request_method = 'GET') {
			add_header 'Access-Control-Allow-Origin' '*' always;
			add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
			add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' always;
			add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range' always;
		}

		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
	}

	# deny access to .htaccess files, if Apache's document root
	# concurs with nginx's one
	location ~ /\.ht { deny all; }
}

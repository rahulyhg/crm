<?php


return array(

	'apiPath' => '/api/v1',

	'requirements' => array(
		'phpVersion' => '5.5',

		'phpRequires' => array(
			'JSON',
			'openssl',
			'pdo_mysql'
		),

		'phpRecommendations' => array(
			'zip',
			'gd',
			'mbstring',
			'imap',
			'curl',
			'max_execution_time' => 180,
			'max_input_time' => 180,
			'memory_limit' => '256M',
			'post_max_size' => '20M',
			'upload_max_filesize' => '20M',
		),

		'mysqlVersion' => '5.1',
		'mysqlRequires' => array(

		),

		'mysqlRecommendations' => array(

		),
	),

	'rewriteRules' => array(
		'APACHE1' => 'a2enmod rewrite
service apache2 restart',
		'APACHE2' => '&#60;Directory /PATH_TO_ESPO/&#62;
 AllowOverride <b>All</b>
&#60;/Directory&#62;',
		'APACHE3' => 'service apache2 restart',
		'APACHE4' => '# RewriteBase /',
		'APACHE5' => 'RewriteBase {ESPO_PATH}{API_PATH}',
		'NGINX' => 'server {
    # ...

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/v1/ {
        if (!-e $request_filename){
            rewrite ^/api/v1/(.*)$ /api/v1/index.php last; break;
        }
    }

    location /portal/ {
        try_files $uri $uri/ /portal/index.php?$query_string;
    }

    location /api/v1/portal-access {
        if (!-e $request_filename){
            rewrite ^/api/v1/(.*)$ /api/v1/portal-access/index.php last; break;
        }
    }

    location ~ /reset/?$ {
        try_files /reset.html =404;
    }

    location ^~ (data|api)/ {
        if (-e $request_filename){
            return 403;
        }
    }
    location ^~ /data/logs/ {
        deny all;
    }
    location ^~ /data/config.php {
        deny all;
    }
    location ^~ /data/cache/ {
        deny all;
    }
    location ^~ /data/upload/ {
        deny all;
    }
    location ^~ /application/ {
        deny all;
    }
    location ^~ /custom/ {
        deny all;
    }
    location ^~ /vendor/ {
        deny all;
    }
    location ~ /\.ht {
        deny all;
    }
}',
	),

	'blog' => 'http://blog.espocrm.com',
	'twitter' => 'https://twitter.com/espocrm',
	'forum' => 'http://forum.espocrm.com',

);
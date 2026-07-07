#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

    if [ ! -f 'vendor/autoload.php' ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    php bin/console -V

    if grep -q ^DATABASE_URL= .env 2>/dev/null || [ -n "$DATABASE_URL" ]; then

        echo 'Waiting for database server to be ready...'
        ATTEMPTS=60
        while [ $ATTEMPTS -gt 0 ]; do
            if php -r "
                \$url = getenv('DATABASE_URL');
                preg_match('#//([^:]+):([^@]+)@([^:/]+):(\d+)#', \$url, \$m);
                try {
                    new PDO('mysql:host='.\$m[3].';port='.\$m[4], \$m[1], \$m[2]);
                    exit(0);
                } catch(Exception \$e) { exit(1); }
            "; then
                echo 'Database server is ready.'
                break
            fi
            ATTEMPTS=$((ATTEMPTS - 1))
            echo "Still waiting... $ATTEMPTS attempts left."
            sleep 1
        done

        if [ $ATTEMPTS -eq 0 ]; then
            echo 'Database server unreachable. Exiting.'
            exit 1
        fi

        echo 'Creating database if needed...'
        php -r "
            \$url = getenv('DATABASE_URL');
            preg_match('#//([^:]+):([^@]+)@([^:/]+):(\d+)/([^?]+)#', \$url, \$m);
            \$pdo = new PDO('mysql:host='.\$m[3].';port='.\$m[4], \$m[1], \$m[2]);
            \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`'.\$m[5].'\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            echo 'Done.' . PHP_EOL;
        "

        if [ "$(find ./migrations -iname '*.php' -print -quit 2>/dev/null)" ]; then
            echo 'Running migrations...'
            php bin/console doctrine:migrations:migrate --no-interaction || true
        fi

    fi

	# if [ "$APP_ENV" = "dev" ]; then
	# 		php bin/console tailwind:build --watch &
	# 	fi

    echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"

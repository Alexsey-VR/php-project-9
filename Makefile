install:
	composer install

update:
	composer update

validate:
	composer validate

PORT ?= 8000
start: init
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

init:
	psql -a -d $(DATABASE_URL) -f database.sql

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public/ src/ tests/

analyze:
	composer exec -v phpstan analyze -- -c vendor/phpstan/phpstan/conf/phpstan.neon --level 10 --ansi src/

dev-init:
	./vendor/bin/phpunit --generate-configuration

test:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text

test-dev:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-html ./reports

compose_command = docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php84

build:
	docker-compose build

shell: build
	$(compose_command) bash

destroy:
	docker-compose down -v

composer: build
	$(compose_command) composer install

lint: build
	$(compose_command) composer lint

refactor: build
	$(compose_command) composer refactor

test: build
	$(compose_command) composer test

test\:lint: build
	$(compose_command) composer test:lint

test\:refactor: build
	$(compose_command) composer test:refactor

test\:type-coverage: build
	$(compose_command) composer test:type-coverage

test\:types: build
	$(compose_command) composer test:types

test\:unit: build
	$(compose_command) composer test:unit

# Run all database and key type combinations (mirrors CI matrix)
test\:docker: test\:docker\:sqlite test\:docker\:mysql test\:docker\:postgres

# Run all key type variations for all databases
test\:docker\:all: test\:docker\:sqlite\:morphs test\:docker\:sqlite\:numeric test\:docker\:sqlite\:ulid test\:docker\:sqlite\:uuid test\:docker\:mysql\:morphs test\:docker\:mysql\:numeric test\:docker\:mysql\:ulid test\:docker\:mysql\:uuid test\:docker\:postgres\:morphs test\:docker\:postgres\:numeric test\:docker\:postgres\:ulid test\:docker\:postgres\:uuid

# SQLite tests (all key types)
test\:docker\:sqlite: test\:docker\:sqlite\:morphs test\:docker\:sqlite\:numeric test\:docker\:sqlite\:ulid test\:docker\:sqlite\:uuid

test\:docker\:sqlite\:morphs: build
	docker-compose run --rm -e DB_CONNECTION=sqlite -e ANCESTRY_PRIMARY_KEY_TYPE=id php84 composer test:morphs

test\:docker\:sqlite\:numeric: build
	docker-compose run --rm -e DB_CONNECTION=sqlite -e ANCESTRY_PRIMARY_KEY_TYPE=id -e ANCESTRY_ANCESTOR_MORPH_TYPE=numeric -e ANCESTRY_DESCENDANT_MORPH_TYPE=numeric php84 composer test:numeric

test\:docker\:sqlite\:ulid: build
	docker-compose run --rm -e DB_CONNECTION=sqlite -e ANCESTRY_PRIMARY_KEY_TYPE=ulid -e ANCESTRY_ANCESTOR_MORPH_TYPE=ulid -e ANCESTRY_DESCENDANT_MORPH_TYPE=ulid php84 composer test:ulid

test\:docker\:sqlite\:uuid: build
	docker-compose run --rm -e DB_CONNECTION=sqlite -e ANCESTRY_PRIMARY_KEY_TYPE=uuid -e ANCESTRY_ANCESTOR_MORPH_TYPE=uuid -e ANCESTRY_DESCENDANT_MORPH_TYPE=uuid php84 composer test:uuid

# MySQL tests (all key types) - run sequentially to avoid parallel DDL conflicts
test\:docker\:mysql: test\:docker\:mysql\:morphs test\:docker\:mysql\:numeric test\:docker\:mysql\:ulid test\:docker\:mysql\:uuid

test\:docker\:mysql\:morphs: build
	docker-compose up -d mysql
	docker-compose run --rm -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=ancestry_test -e DB_USERNAME=root -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=id php84 composer test:seq:morphs

test\:docker\:mysql\:numeric: build
	docker-compose up -d mysql
	docker-compose run --rm -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=ancestry_test -e DB_USERNAME=root -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=id -e ANCESTRY_ANCESTOR_MORPH_TYPE=numeric -e ANCESTRY_DESCENDANT_MORPH_TYPE=numeric php84 composer test:seq:numeric

test\:docker\:mysql\:ulid: build
	docker-compose up -d mysql
	docker-compose run --rm -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=ancestry_test -e DB_USERNAME=root -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=ulid -e ANCESTRY_ANCESTOR_MORPH_TYPE=ulid -e ANCESTRY_DESCENDANT_MORPH_TYPE=ulid php84 composer test:seq:ulid

test\:docker\:mysql\:uuid: build
	docker-compose up -d mysql
	docker-compose run --rm -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=ancestry_test -e DB_USERNAME=root -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=uuid -e ANCESTRY_ANCESTOR_MORPH_TYPE=uuid -e ANCESTRY_DESCENDANT_MORPH_TYPE=uuid php84 composer test:seq:uuid

# PostgreSQL tests (all key types) - run sequentially to avoid parallel DDL conflicts
test\:docker\:postgres: test\:docker\:postgres\:morphs test\:docker\:postgres\:numeric test\:docker\:postgres\:ulid test\:docker\:postgres\:uuid

test\:docker\:postgres\:morphs: build
	docker-compose up -d postgres
	docker-compose run --rm -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=ancestry_test -e DB_USERNAME=postgres -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=id php84 composer test:seq:morphs

test\:docker\:postgres\:numeric: build
	docker-compose up -d postgres
	docker-compose run --rm -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=ancestry_test -e DB_USERNAME=postgres -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=id -e ANCESTRY_ANCESTOR_MORPH_TYPE=numeric -e ANCESTRY_DESCENDANT_MORPH_TYPE=numeric php84 composer test:seq:numeric

test\:docker\:postgres\:ulid: build
	docker-compose up -d postgres
	docker-compose run --rm -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=ancestry_test -e DB_USERNAME=postgres -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=ulid -e ANCESTRY_ANCESTOR_MORPH_TYPE=ulid -e ANCESTRY_DESCENDANT_MORPH_TYPE=ulid php84 composer test:seq:ulid

test\:docker\:postgres\:uuid: build
	docker-compose up -d postgres
	docker-compose run --rm -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=ancestry_test -e DB_USERNAME=postgres -e DB_PASSWORD=password -e ANCESTRY_PRIMARY_KEY_TYPE=uuid -e ANCESTRY_ANCESTOR_MORPH_TYPE=uuid -e ANCESTRY_DESCENDANT_MORPH_TYPE=uuid php84 composer test:seq:uuid

# Local tests (without Docker) for different primary key types
test\:local: test\:local\:morphs

test\:local\:morphs:
	composer test:morphs

test\:local\:numeric:
	composer test:numeric

test\:local\:ulid:
	composer test:ulid

test\:local\:uuid:
	composer test:uuid

test\:local\:all: test\:local\:morphs test\:local\:numeric test\:local\:ulid test\:local\:uuid

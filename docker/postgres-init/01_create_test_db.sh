#!/bin/bash
set -e
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE ${TEST_DB_DATABASE:-laravel_test};
    CREATE USER ${TEST_DB_USERNAME:-laravel_test} WITH PASSWORD '${TEST_DB_PASSWORD:-laravel_test}';
    GRANT ALL PRIVILEGES ON DATABASE ${TEST_DB_DATABASE:-laravel_test} TO ${TEST_DB_USERNAME:-laravel_test};
EOSQL
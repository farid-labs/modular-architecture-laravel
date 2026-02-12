#!/bin/bash
set -e
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE ${TEST_DB_DATABASE:-laravel_test};
    CREATE USER ${TEST_DB_USERNAME:-laravel_test} WITH PASSWORD '${TEST_DB_PASSWORD:-laravel_test}';
    GRANT ALL PRIVILEGES ON DATABASE ${TEST_DB_DATABASE:-laravel_test} TO ${TEST_DB_USERNAME:-laravel_test};

    -- Change the owner of the database and schema to laravel_test
    ALTER DATABASE ${TEST_DB_DATABASE:-laravel_test} OWNER TO ${TEST_DB_USERNAME:-laravel_test};
    \c ${TEST_DB_DATABASE:-laravel_test} ${TEST_DB_USERNAME:-laravel_test}
    ALTER SCHEMA public OWNER TO ${TEST_DB_USERNAME:-laravel_test};
EOSQL
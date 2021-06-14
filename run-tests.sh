#!/usr/bin/env bash
set -o errexit -o nounset -o pipefail

cleanup() {
    if [[ -z "${CACHET_KEEP:-}" ]]; then
        docker-compose stop
    fi
}

trap cleanup EXIT

docker-compose up -d --remove-orphans

# Wait for ports because apparently this is too hard for docker-compose:
until nc -z localhost 3307;  do sleep 0.5; done
until nc -z localhost 6740;  do sleep 0.5; done
until nc -z localhost 11212; do sleep 0.5; done

setup_args=(
    -w /cachet 
    -e "CACHET_CONFIG=/cachet/test/docker-cachettestrc"
)
run_args=(
    /bin/bash -c ./vendor/bin/phpunit
)

for phpver in php73 php74 php80; do
    docker-compose run "${setup_args[@]}" "$phpver" "${run_args[@]}"
done


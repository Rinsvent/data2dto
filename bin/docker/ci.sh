#!/bin/bash

docker-compose -f ./docker-compose-ci.yml up -d

echo 'composer installing'
docker exec -i data2dto_php composer install -q
echo 'composer installed !!'

docker exec -i data2dto_php vendor/bin/codecept run --coverage

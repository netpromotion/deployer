all: .build composer-update tests

.PHONY: *

.build:
	sudo docker rm netpromotion/deployer || true
	sudo docker build -t netpromotion/deployer .

.run:
	sudo docker run -v $$(pwd):/app --rm netpromotion/deployer bash -c 'cd /app && ${ARGS}'

composer:
	make .run ARGS="composer ${ARGS}"

composer-update:
	make composer ARGS="update ${ARGS}"

tests:
	make .run ARGS="php vendor/bin/phpunit ${ARGS}"

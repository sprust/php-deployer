PHP_SERVICE="php"

env-copy:
	cp -i .env.example .env

bash:
	docker-compose run --rm $(PHP_SERVICE) bash

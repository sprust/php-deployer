env-copy:
	cp -i .env.example .env

test:
	php deployer test

deploy:
	php deployer deploy

config:
	cp -i .env.example .env
	cp -i symlinks.json.example symlinks.json

test:
	php deployer test

deploy:
	php deployer deploy

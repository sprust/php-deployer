ifneq (,$(wildcard ./.env))
    include .env
    export
endif

env-copy:
	cp -i .env.example .env

test-clone:
	rm -rf releases/release_clone_test \
		&& mkdir releases/release_clone_test \
		&& cd releases/release_clone_test \
		&& git clone --branch $(BRANCH) $(REPOSITORY) .

deploy:
	php deployer deploy

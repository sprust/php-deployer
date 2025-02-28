ifneq (,$(wildcard ./.env))
    include .env
    export
endif

env-copy:
	cp -i .env.example .env

test-clone:
	rm -rf build/build_clone_test \
		&& mkdir build/build_clone_test \
		&& cd build/build_clone_test \
		&& git clone --branch $(BRANCH) $(REPOSITORY) .

deploy:
	php deployer deploy

PHP_SERVICE="php"

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

env-copy:
	cp -i .env.example .env

ssh-pub-key:
	docker-compose run --rm $(PHP_SERVICE) cat /root/.ssh/id_rsa.pub

test-clone:
	docker-compose run --rm $(PHP_SERVICE) rm -rf build/build_clone_test \
		&& mkdir build/build_clone_test \
		&& cd build/build_clone_test \
		&& git clone --branch $(BRANCH) $(REPOSITORY) .

bash:
	docker-compose run --rm $(PHP_SERVICE) bash

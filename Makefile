all: clean coverage docs

start-server:
	@ps aux | grep 'node tests/server.js' | grep -v grep > /dev/null \
	|| node tests/Client/server.js &> /dev/null &

stop-server:
	@PID=$(shell ps axo pid,command | grep 'tests/Client/server.js' | grep -v grep | cut -f 1 -d " ") && \
	[ -n "$$PID" ] && \
	kill $$PID || \
	true

test: start-server
	vendor/bin/phpunit
	$(MAKE) stop-server

coverage: start-server
	vendor/bin/phpunit --coverage-html=build/artifacts/coverage
	$(MAKE) stop-server

view-coverage:
	open build/artifacts/coverage/index.html

clean:
	rm -rf build/artifacts/*

tag:
	$(if $(TAG),,$(error TAG is not defined. Pass via "make tag TAG=4.2.1"))
	@echo Tagging $(TAG)
	chag update -m '$(TAG) ()'
	git add -A
	git commit -m '$(TAG) release'
	chag tag

perf: start-server
	php tests/perf.php
	$(MAKE) stop-server

.PHONY: doc

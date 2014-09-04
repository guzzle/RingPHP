all: clean test

test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html=build/artifacts/coverage

view-coverage:
	open build/artifacts/coverage/index.html

clean:
	rm -rf artifacts/*

.PHONY: coverage

all: install test

test:
	prove -r t/

install:
	composer install

clean:
	rm -rf build
	rm -rf vendor

build:
	mkdir -p build/staging
	curl -s https://raw.githubusercontent.com/mtdowling/Burgomaster/0.0.2/src/Burgomaster.php > build/Burgomaster.php
	php packager.php

.PHONY: test build

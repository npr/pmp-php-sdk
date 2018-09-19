all: install test

test:
	prove -r t/

install:
	composer install

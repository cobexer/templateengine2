#
# TemplateEngine2 PHP Templating System $VERSION$
# http://gruewo.dyndns.org/gitweb/?p=templateengine2.git
#
# @copyright Copyright 2010, Obexer Christoph
# Dual licensed under the MIT or GPL Version 2 licenses.
#
# Date: $DATE$
# @author Obexer Christoph
# @version $VERSION$
# @package TemplateEngine2
#


all: clean tests-coverage

.PHONY: release
release: build replace-version
	#TODO: create release package here

.PHONY: replace-version
replace-version: build
	#TODO: find out current version && git revision


.PHONY: tests
tests: build
	phpunit --process-isolation --no-globals-backup tests/


.PHONY: coverage
coverage: build
	phpunit --process-isolation --no-globals-backup --coverage-html build/coverage/ \
		--coverage-clover build/coverage-clover.xml --log-junit build/junit-test-log.xml tests/

.PHONY: clean
clean:
	rm -rfv build/

build:
	mkdir -pv build/

# from http://www.phpunit.de/manual/current/en/installation.html
install-phpunit:
	pear channel-discover pear.phpunit.de
	pear channel-discover components.ez.no
	pear channel-discover pear.symfony-project.com
	pear install phpunit/PHPUnit

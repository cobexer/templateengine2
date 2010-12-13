#
# TemplateEngine2 PHP Templating System
# @copyright Copyright 2010, Obexer Christoph
# Dual licensed under the MIT or GPL Version 2 licenses.
# @author Obexer Christoph
#

BUILD_DIR = build
TESTS_DIR = tests
PLUGINS_DIR = plugins

TE_VER = $(shell cat version.txt)
VER = sed "s/@VERSION@/${TE_VER}/"
TE_DATE = $(shell git log -1 --pretty=format:%ad)
DATE = sed "s/@DATE@/${TE_DATE}/"
TE_COMMIT = $(shell git log -1 --pretty=format:%H)
COMMIT = sed "s/@COMMIT@/${TE_COMMIT}/"
TE_WWW = "http://gruewo.dyndns.org/gitweb/?p=templateengine2.git"
WWW = sed "s|@WWW@|${TE_WWW}|"


PLUGINS = $(shell find ${PLUGINS_DIR} -name "*.php" -exec sh -c "echo {} | sed s@${PLUGINS_DIR}@${BUILD_DIR}/${PLUGINS_DIR}@" \;)

all: clean tests release

${BUILD_DIR}/${PLUGINS_DIR}/%.php: ${PLUGINS_DIR}/%.php
	@echo "patching $< to $@"
	@cat $< | $(VER) | $(DATE) | $(COMMIT) | $(WWW) > $@


release: build ${PLUGINS}
	@cat TemplateEngine2.php | $(VER) | $(DATE) | $(COMMIT) | $(WWW) > ${BUILD_DIR}/TemplateEngine2.php
	@echo "built release version: TemplateEngine2 ${TE_VER} of ${TE_DATE} (${TE_COMMIT})"

tests:
	phpunit --process-isolation --no-globals-backup ${TESTS_DIR}/


coverage: build
	@phpunit --process-isolation --no-globals-backup --coverage-html ${BUILD_DIR}/coverage/ \
		--coverage-clover ${BUILD_DIR}/coverage-clover.xml --log-junit ${BUILD_DIR}/junit-test-log.xml ${TESTS_DIR}/


clean:
	@rm -rfv ${BUILD_DIR}


build:
	@mkdir -pv ${BUILD_DIR}/${PLUGINS_DIR}/


# from http://www.phpunit.de/manual/current/en/installation.html
install-phpunit:
	pear channel-discover pear.phpunit.de
	pear channel-discover components.ez.no
	pear channel-discover pear.symfony-project.com
	pear install phpunit/PHPUnit

.PHONY: release tests coverage clean

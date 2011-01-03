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

TE_RELEASE_NAME = ${BUILD_DIR}/TemplateEngine2.php

PLUGINS = $(shell find ${PLUGINS_DIR} -name "*.php" -exec sh -c "echo {} | sed s@${PLUGINS_DIR}@${BUILD_DIR}/${PLUGINS_DIR}@" \;)

release: build ${PLUGINS} release-plugins.txt
	@test -e TemplateEngine2.php  || (echo "ERROR: TemplateEngine2.php missing!" && exit 1)
	@cat TemplateEngine2.php | $(VER) | $(DATE) | $(COMMIT) | $(WWW) > ${TE_RELEASE_NAME}
	$(MAKE) -s te2-append-plugins
	@sed -i "s@//EOF@@" ${TE_RELEASE_NAME}
	@echo "//EOF" >> ${TE_RELEASE_NAME}
	@cat -s ${TE_RELEASE_NAME} > ${TE_RELEASE_NAME}.tmp
	@mv ${TE_RELEASE_NAME}.tmp ${TE_RELEASE_NAME}
	@echo "built release version: TemplateEngine2 ${TE_VER} of ${TE_DATE} (${TE_COMMIT})"

release-plugins.txt:
	@cp -v release-plugins.txt.in release-plugins.txt

${BUILD_DIR}/${PLUGINS_DIR}/%.php: ${PLUGINS_DIR}/%.php
	@echo "patching $< to $@"
	@test -e $<  || (echo "ERROR: $< missing!" && exit 1)
	@cat $< | $(VER) | $(DATE) | $(COMMIT) | $(WWW) > $@

TE_RELEASE_PLUGINS = $(shell cat release-plugins.txt 2>/dev/null)
te2-append-plugins: plugins-tests-dirs ${TE_RELEASE_PLUGINS}

plugins-tests-dirs:
	@mkdir -pv ${BUILD_DIR}/${TESTS_DIR}/plugins/
	@mkdir -pv ${BUILD_DIR}/${TESTS_DIR}/templates/

${TE_RELEASE_PLUGINS}:
	@echo "appending ${BUILD_DIR}/${PLUGINS_DIR}/$@.php to ${TE_RELEASE_NAME}..."
	@tail -n +14 ${BUILD_DIR}/${PLUGINS_DIR}/$@.php >> ${TE_RELEASE_NAME}
	@echo "copying tests for $@..."
	@test -e ${TESTS_DIR}/plugins/$@_Test.php || (echo "ERROR: ${TESTS_DIR}/plugins/$@_Test.php missing!" && exit 1)
	@cat ${TESTS_DIR}/plugins/$@_Test.php | $(VER) | $(DATE) | $(COMMIT) | $(WWW) > ${BUILD_DIR}/${TESTS_DIR}/plugins/$@_Test.php
	@cp -rfv ${TESTS_DIR}/templates/plugins/$@ ${BUILD_DIR}/${TESTS_DIR}/templates/plugins/

tests:
	phpunit --process-isolation --no-globals-backup ${TESTS_DIR}/


coverage: build
	@phpunit --process-isolation --no-globals-backup --coverage-html ${BUILD_DIR}/coverage/ \
		--coverage-clover ${BUILD_DIR}/coverage-clover.xml --log-junit ${BUILD_DIR}/junit-test-log.xml ${TESTS_DIR}/


clean:
	@rm -rfv ${BUILD_DIR}

dist-clean: clean
	@rm -rfv release-plugins.txt


build:
	@mkdir -pv ${BUILD_DIR}/${PLUGINS_DIR}/


# from http://www.phpunit.de/manual/current/en/installation.html
install-phpunit:
	pear channel-discover pear.phpunit.de
	pear channel-discover components.ez.no
	pear channel-discover pear.symfony-project.com
	pear install phpunit/PHPUnit

.PHONY: release tests coverage clean build dist-clean

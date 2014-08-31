#
# TemplateEngine2 PHP Templating System
# @copyright Copyright 2011-2014, Obexer Christoph
# Dual licensed under the MIT or GPL Version 2 licenses.
# @author Obexer Christoph
#

BUILD_DIR = build
TESTS_DIR = tests
PLUGINS_DIR = plugins

TE_VER := $(shell cat version.txt)
VER := sed "s/@VERSION@/${TE_VER}/"
TE_DATE := $(shell git log -1 --pretty=format:%ad)
DATE := sed "s/@DATE@/${TE_DATE}/"
TE_COMMIT := $(shell git log -1 --pretty=format:%H)
COMMIT := sed "s/@COMMIT@/${TE_COMMIT}/"
TE_WWW := "https://github.com/cobexer/templateengine2"
WWW := sed "s|@WWW@|${TE_WWW}|"
TE_PREP_RELEASE := sed "s@/\* RM \*/.*/\* /RM \*/@@g"


TE_RELEASE_NAME := ${BUILD_DIR}/TemplateEngine2.php

PLUGINS := $(addprefix ${BUILD_DIR}/,$(wildcard ${PLUGINS_DIR}/*.php))

process_content = $(shell cat $(1) | $(VER) | $(DATE) | $(COMMIT) | $(WWW) | $(TE_PREP_RELEASE) > $(2))

process = $(if $(shell test -e $(1) && echo "1"),$(call process_content,$(1),$(2)),$(error ERROR: $(1) missing!))

release: clean build-plugins-dir ${PLUGINS} release-plugins.txt export-base-tests
	echo "${TE_VER}" > ${BUILD_DIR}/version.txt
	$(call process,TemplateEngine2.php,${TE_RELEASE_NAME})
	$(call process,TE_setup2.php,${BUILD_DIR}/TE_setup2.php)
	$(call process,Makefile,${BUILD_DIR}/Makefile)
	$(call process,MIT-LICENSE.txt,${BUILD_DIR}/MIT-LICENSE.txt)
	$(call process,GPL-LICENSE.txt,${BUILD_DIR}/GPL-LICENSE.txt)
	$(call process,phpunit.xml,${BUILD_DIR}/phpunit.xml)
	@cp -fv ${TE_RELEASE_NAME} $(subst .php,.debug.php,${TE_RELEASE_NAME})
	$(Q)$(MAKE) te2-append-plugins
	@sed -i "s@//EOF@@" ${TE_RELEASE_NAME}
	@echo "//EOF" >> ${TE_RELEASE_NAME}
	@cat -s ${TE_RELEASE_NAME} > ${TE_RELEASE_NAME}.tmp
	@mv ${TE_RELEASE_NAME}.tmp ${TE_RELEASE_NAME}
ifneq ($(TESTS),skip)
	$(Q)$(MAKE) -C ${BUILD_DIR}/ tests
endif
	@echo "built release version: TemplateEngine2 ${TE_VER} of ${TE_DATE} (${TE_COMMIT})"

doc:
	@mkdir -p ${BUILD_DIR}/documentation/
	@pdflatex -output-directory ${BUILD_DIR}/documentation/ -interaction=nonstopmode documentation/TemplateEngine2.tex
	@pdflatex -output-directory ${BUILD_DIR}/documentation/ -interaction=nonstopmode documentation/TemplateEngine2.tex
	@cp ${BUILD_DIR}/documentation/TemplateEngine2.pdf ${BUILD_DIR}/
	@rm -rf ${BUILD_DIR}/documentation/

BASE_TESTS := $(addprefix ${BUILD_DIR}/,$(wildcard ${TESTS_DIR}/*.php))

${BASE_TESTS}:
	$(call process,$(subst ${BUILD_DIR}/,,$@),$@)

test-export-dir:
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/templates/
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/templates/base-template/
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/templates/jail-test/


export-base-tests: test-export-dir ${BASE_TESTS}
	@cp -r ${TESTS_DIR}/templates/*.tpl ${BUILD_DIR}/${TESTS_DIR}/templates/
	@cp -r ${TESTS_DIR}/templates/base-template/*.tpl ${BUILD_DIR}/${TESTS_DIR}/templates/base-template/

#$(foreach tst,$(wildcard ${TESTS_DIR}/*.php),$(call process,$(tst),${BUILD_DIR}/$(tst)))


release-plugins.txt: release-plugins.txt.in
	@cp release-plugins.txt.in release-plugins.txt

build-plugins-dir:
	@mkdir -p ${BUILD_DIR}/plugins/

${BUILD_DIR}/${PLUGINS_DIR}/%.php: ${PLUGINS_DIR}/%.php
	@echo "patching $< to $@"
	$(call process,$<,$@)

TE_RELEASE_PLUGINS := $(shell cat release-plugins.txt 2>/dev/null)
te2-append-plugins: plugins-tests-dirs ${TE_RELEASE_PLUGINS}

plugins-tests-dirs:
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/plugins/
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/templates/plugins/
	@mkdir -p ${BUILD_DIR}/${TESTS_DIR}/templates/base-template/plugins/

${TE_RELEASE_PLUGINS}:
	@echo "processing plugin $@..."
	@tail -n +14 ${BUILD_DIR}/${PLUGINS_DIR}/$@.php >> ${TE_RELEASE_NAME}
	$(call process,${TESTS_DIR}/plugins/$@_Test.php,${BUILD_DIR}/${TESTS_DIR}/plugins/$@_Test.php)
	@if [ -d ${TESTS_DIR}/templates/plugins/$@ ]; then \
		cp -rf ${TESTS_DIR}/templates/plugins/$@ ${BUILD_DIR}/${TESTS_DIR}/templates/plugins/; \
	fi
	@if [ -d ${TESTS_DIR}/templates/base-template/plugins/$@ ]; then \
		cp -rf ${TESTS_DIR}/templates/base-template/plugins/$@ ${BUILD_DIR}/${TESTS_DIR}/templates/base-template/plugins/; \
	fi

tests:
	@phpunit -c phpunit.xml --tap


coverage:
	@mkdir -p ${BUILD_DIR}/coverage/
	@phpunit -c phpunit.xml --tap --coverage-html ${BUILD_DIR}/coverage/

clean:
	rm -rf ${BUILD_DIR}

dist-clean: clean
	@rm -rfv release-plugins.txt


# from http://www.phpunit.de/manual/current/en/installation.html
install-phpunit:
	pear channel-discover pear.phpunit.de
	pear channel-discover components.ez.no
	pear channel-discover pear.symfony-project.com
	pear channel-discover pear.symfony.com
	pear install phpunit/PHPUnit

.PHONY: release tests coverage clean build dist-clean

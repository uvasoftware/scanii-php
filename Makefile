help:
	@echo "Please use \`make <target>' where <target> is one of"
	@echo "  test           to perform unit tests.  Provide TEST to perform a specific test."
	@echo "  coverage       to perform unit tests with code coverage. Provide TEST to perform a specific test."
	@echo "  coverage-show  to show the code coverage report"
	@echo "  clean          to remove build artifacts"
	@echo "  docs           to build the Sphinx docs"
	@echo "  docs-show      to view the Sphinx docs"
	@echo "  tag            to modify the version, update changelog, and change tag"
	@echo "  package        to build the phar and zip files"

test:
	vendor/bin/phpunit --bootstrap vendor/autoload.php tests/*

clean:
	rm -rf artifacts/*

docs:
	cd docs && make html && cd ..

docs-show:
	open docs/_build/html/index.html

tag:
	$(if $(TAG),,$(error TAG is not defined. Pass via "make tag TAG=4.2.1"))
	@echo Tagging $(TAG)
	chag update $(TAG)
	sed -i '' -e "s/VERSION = '.*'/VERSION = '$(TAG)'/" src/Scanii/ScaniiClient.php
	php -l src/Scanii/ScaniiClient.php
	git add -A
	git commit -m '$(TAG) release'

.PHONY: docs burgomaster coverage-show view-coverage

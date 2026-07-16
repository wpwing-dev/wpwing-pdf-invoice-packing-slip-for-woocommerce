PLUGIN_SLUG = wpwing-pdf-invoice-packing-slip-for-woocommerce
SRC_DIR     = src
DIST_DIR    = dist
BUILD_DIR   = $(DIST_DIR)/$(PLUGIN_SLUG)

PRO_SLUG    = wpwing-pdf-invoice-packing-slip-pro
PRO_SRC     = pro
PRO_BUILD   = $(DIST_DIR)/$(PRO_SLUG)

# Dev tooling (PHPUnit 10, phpcs, phpstan) needs PHP 8.1+, independent of the system default php
PHP      ?= php8.2
COMPOSER ?= $(PHP) $(shell command -v composer)

.DEFAULT_GOAL := help

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
.PHONY: help

assets: ## Compile SCSS and minify JS
	npm run build:css
	npm run build:js
.PHONY: assets

watch: ## Watch SCSS for changes (Ctrl+C to stop)
	npm run watch:css
.PHONY: watch

phpcs: ## Run PHP_CodeSniffer and report errors
	$(PHP) ./vendor/bin/phpcs
.PHONY: phpcs

phpcbf: ## Auto-fix PHP code with PHP Code Beautifier and Fixer
	$(PHP) ./vendor/bin/phpcbf
.PHONY: phpcbf

lint-js: ## Lint JavaScript with ESLint
	npm run lint:js
.PHONY: lint-js

lint-css: ## Lint SCSS with Stylelint
	npm run lint:css
.PHONY: lint-css

lint-all: phpcs lint-js lint-css ## Run all linters (PHP, JS, SCSS)
.PHONY: lint-all

analyse: ## Run PHPStan static analysis
	$(PHP) ./vendor/bin/phpstan analyse
.PHONY: analyse

pot: ## Regenerate the .pot translation file
	wp i18n make-pot $(SRC_DIR) $(SRC_DIR)/languages/wpwing-wcpdf.pot \
		--exclude=vendor,node_modules
.PHONY: pot

version: ## Bump version strings — usage: make version V=1.6.0
	@[ -n "$(V)" ] || (echo "Usage: make version V=1.6.0" && exit 1)
	sed -i "s/Version: .*/Version: $(V)/" $(SRC_DIR)/$(PLUGIN_SLUG).php
	sed -i "s/WPWING_WCPDF_VERSION', '[0-9.]*'/WPWING_WCPDF_VERSION', '$(V)'/" $(SRC_DIR)/$(PLUGIN_SLUG).php
	sed -i "s/\"version\": \".*\"/\"version\": \"$(V)\"/" package.json
	sed -i "s/Stable tag: .*/Stable tag: $(V)/" $(SRC_DIR)/readme.txt
	@echo "Version bumped to $(V)"
.PHONY: version

zip: clean-build assets ## Build distributable zip into dist/
	mkdir -p $(BUILD_DIR)
	rsync -r --exclude-from=.distignore $(SRC_DIR)/ $(BUILD_DIR)/
	$(COMPOSER) install --no-dev --optimize-autoloader --working-dir=$(BUILD_DIR)
	rm -f $(BUILD_DIR)/composer.json $(BUILD_DIR)/composer.lock
	cd $(DIST_DIR) && zip -r $(PLUGIN_SLUG).zip $(PLUGIN_SLUG)/
	rm -rf $(BUILD_DIR)
	unzip -t $(DIST_DIR)/$(PLUGIN_SLUG).zip > /dev/null
	@echo "Built: $(DIST_DIR)/$(PLUGIN_SLUG).zip"
.PHONY: zip

release: ## Full release — usage: make release V=1.6.0
	@[ -n "$(V)" ] || (echo "Usage: make release V=1.6.0" && exit 1)
	$(MAKE) version V=$(V)
	$(MAKE) check
	$(MAKE) phpcs
	$(MAKE) zip
	@echo ""
	@echo "Next: git commit -am 'Release v$(V)' && make tag V=$(V) && git push && git push --tags"
.PHONY: release

vendor-prod: ## Install Composer deps without dev packages
	$(COMPOSER) install --no-dev --optimize-autoloader --working-dir=$(SRC_DIR)
.PHONY: vendor-prod

clean-build: ## Remove staging build dir
	rm -rf $(BUILD_DIR)
.PHONY: clean-build

clean: ## Remove the entire dist directory
	rm -rf $(DIST_DIR)
.PHONY: clean

dist: zip zip-pro ## Build both FREE and PRO plugin zips into dist/
.PHONY: dist

zip-pro: clean-build-pro ## Build pro addon zip into dist/
	mkdir -p $(PRO_BUILD)
	rsync -r --exclude='.gitignore' $(SRC_DIR)/$(PRO_SRC)/ $(PRO_BUILD)/
	cd $(DIST_DIR) && zip -r $(PRO_SLUG).zip $(PRO_SLUG)/
	rm -rf $(PRO_BUILD)
	@echo "Built: $(DIST_DIR)/$(PRO_SLUG).zip"
.PHONY: zip-pro

version-pro: ## Bump pro version strings — usage: make version-pro V=1.0.1
	@[ -n "$(V)" ] || (echo "Usage: make version-pro V=1.0.1" && exit 1)
	sed -i "s/Version: .*/Version: $(V)/" $(SRC_DIR)/$(PRO_SRC)/$(PRO_SLUG).php
	sed -i "s/define( 'WPWING_WCPDF_PRO_VERSION', '.*' )/define( 'WPWING_WCPDF_PRO_VERSION', '$(V)' )/" $(SRC_DIR)/$(PRO_SRC)/$(PRO_SLUG).php
	@echo "Pro version bumped to $(V)"
.PHONY: version-pro

release-pro: ## Full pro release — usage: make release-pro V=1.0.1
	@[ -n "$(V)" ] || (echo "Usage: make release-pro V=1.0.1" && exit 1)
	$(MAKE) version-pro V=$(V)
	$(MAKE) zip-pro
	@echo ""
	@echo "Next: git commit -am 'Release pro v$(V)' && git tag -a pro-v$(V) -m 'Release pro v$(V)' && git push --tags"
.PHONY: release-pro

clean-build-pro: ## Remove pro staging build dir
	rm -rf $(PRO_BUILD)
.PHONY: clean-build-pro

check: ## Verify version strings are consistent across all files
	@V=$$(grep 'Version:' $(SRC_DIR)/$(PLUGIN_SLUG).php | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1); \
	echo "Checking version $$V …"; \
	errors=0; \
	grep -q "\"version\": \"$$V\"" package.json                    || { echo "  FAIL: version in package.json"; errors=1; }; \
	grep -qP "Stable tag:\s+$$V" $(SRC_DIR)/readme.txt             || { echo "  FAIL: Stable tag in readme.txt"; errors=1; }; \
	grep -q "WPWING_WCPDF_VERSION', '$$V'" $(SRC_DIR)/$(PLUGIN_SLUG).php || { echo "  FAIL: WPWING_WCPDF_VERSION constant"; errors=1; }; \
	[ $$errors -eq 0 ] && echo "  All version strings match $$V ✓" || exit 1
.PHONY: check

tag: ## Create annotated git tag — usage: make tag V=1.6.0
	@[ -n "$(V)" ] || (echo "Usage: make tag V=1.6.0" && exit 1)
	git tag -a "v$(V)" -m "Release v$(V)"
	@echo "Tagged v$(V) — push with: git push --tags"
.PHONY: tag

setup: ## Bootstrap dev environment (first-time setup)
	$(COMPOSER) install --no-interaction
	$(COMPOSER) install --no-interaction --working-dir=$(SRC_DIR)
	npm install
	@printf '#!/bin/sh\nmake phpcs\n' > .git/hooks/pre-push
	@chmod +x .git/hooks/pre-push
	@echo "Dev environment ready. Run 'make assets' to build CSS/JS."
.PHONY: setup

changelog: ## Print commits since last tag to help update CHANGELOG.md
	@LAST=$$(git describe --tags --abbrev=0 2>/dev/null); \
	if [ -n "$$LAST" ]; then \
		echo "Commits since $$LAST:"; \
		git log $$LAST..HEAD --pretty=format:"- %s" --no-merges; \
	else \
		echo "No tags yet — showing all commits:"; \
		git log --pretty=format:"- %s" --no-merges; \
	fi
.PHONY: changelog

dev: ## Start local WordPress dev environment
	docker compose --profile dev up -d --wait db wordpress
	docker compose --profile dev run --rm wpcli
	docker compose --profile dev up -d caddy
	@echo ""
	@echo "Site:  https://pdf-invoice.local"
	@echo "Admin: https://pdf-invoice.local/wp-admin  (admin / password)"
	@echo ""
	@echo "First time? Run: make caddy-trust"
.PHONY: dev

caddy-trust: ## Trust Caddy's local CA (run once per machine, requires sudo)
	@echo "Waiting for Caddy to generate its CA..."
	@sleep 3
	docker compose --profile dev cp caddy:/data/caddy/pki/authorities/local/root.crt /tmp/caddy-root.crt
	sudo cp /tmp/caddy-root.crt /usr/local/share/ca-certificates/caddy-local.crt
	sudo update-ca-certificates
	@echo "Done. Restart your browser."
.PHONY: caddy-trust

dev-stop: ## Stop the dev environment
	docker compose --profile dev down
.PHONY: dev-stop

test-unit: ## Run PHPUnit tests (no WordPress stack needed)
	docker compose --profile unit run --rm unit
.PHONY: test-unit

test-e2e: ## Run Playwright E2E tests (spins up full stack)
	docker compose --profile e2e up -d --wait db wordpress
	docker compose --profile e2e run --rm wpcli
	docker compose --profile e2e up -d caddy
	docker compose --profile e2e run --rm e2e
.PHONY: test-e2e

env-reset: ## Wipe all Docker volumes and containers
	docker compose --profile dev --profile unit --profile e2e down -v
.PHONY: env-reset

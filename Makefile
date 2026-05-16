PLUGIN_SLUG = wpwing-pdf-invoice-packing-slip-for-woocommerce
DIST_DIR    = dist
BUILD_DIR   = $(DIST_DIR)/$(PLUGIN_SLUG)

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

lint: ## Run PHP_CodeSniffer with WordPress standards
	./vendor/bin/phpcs
.PHONY: lint

pot: ## Regenerate the .pot translation file
	wp i18n make-pot . languages/wpwing-wc-pdf-invoice.pot \
		--exclude=vendor,node_modules,dist
.PHONY: pot

version: ## Bump version strings — usage: make version V=1.6.0
	@[ -n "$(V)" ] || (echo "Usage: make version V=1.6.0" && exit 1)
	sed -i "s/Version: .*/Version: $(V)/" $(PLUGIN_SLUG).php
	sed -i "s/\"version\": \".*\"/\"version\": \"$(V)\"/" package.json
	sed -i "s/Stable tag: .*/Stable tag: $(V)/" readme.txt
	@echo "Version bumped to $(V)"
.PHONY: version

zip: clean-build assets vendor-prod ## Build distributable zip into dist/
	mkdir -p $(BUILD_DIR)
	rsync -r --exclude-from=.distignore . $(BUILD_DIR)/
	cd $(DIST_DIR) && zip -r $(PLUGIN_SLUG).zip $(PLUGIN_SLUG)/
	rm -rf $(BUILD_DIR)
	composer install
	@echo "Built: $(DIST_DIR)/$(PLUGIN_SLUG).zip"
.PHONY: zip

release: ## Full release — usage: make release V=1.6.0
	@[ -n "$(V)" ] || (echo "Usage: make release V=1.6.0" && exit 1)
	$(MAKE) version V=$(V)
	$(MAKE) lint
	$(MAKE) zip
.PHONY: release

vendor-prod: ## Install Composer deps without dev packages
	composer install --no-dev --optimize-autoloader
.PHONY: vendor-prod

clean-build: ## Remove staging build dir
	rm -rf $(BUILD_DIR)
.PHONY: clean-build

clean: ## Remove the entire dist directory
	rm -rf $(DIST_DIR)
.PHONY: clean

.PHONY: ${TARGETS}
.DEFAULT_GOAL := help

help:
	@echo "\033[1;36mAVAILABLE COMMANDS :\033[0m"
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[33m%s\033[0m\n", substr($$0, 5) } ' Makefile

##@ Base commands
install: vendors-install ## Install the project

vendors-install: tools/phpstan/composer.lock tools/php-cs-fixer/composer.lock ## Install vendors
	@composer ins

vendors-update: ## Update all vendors
	@composer up
	@composer --working-dir tools/php-cs-fixer up
	@composer --working-dir tools/phpstan up

tools/phpstan/composer.lock:
	@composer install --working-dir=tools/phpstan

tools/php-cs-fixer/composer.lock:
	@composer install --working-dir=tools/php-cs-fixer

##@ Quality commands
test: ## Run tests
	@vendor/bin/phpunit

phpstan: tools/phpstan/composer.lock ## Run PHPStan
	@tools/phpstan/vendor/bin/phpstan analyse --memory-limit=512M

cs-lint: tools/php-cs-fixer/composer.lock ## Lint all files
	@tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: tools/php-cs-fixer/composer.lock ## Fix CS using PHP-CS
	@tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

.PHONY: cs
cs: vendor
	mkdir -p .build/php-cs-fixer
	symfony php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

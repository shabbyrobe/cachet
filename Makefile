fix:
	php-cs-fixer fix -vv --level=all \
		--fixers=-braces,-visibility,-return,-phpdoc_params src/

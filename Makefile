swaml.phar = dist/swaml.phar

${swaml.phar}: src/*.php src/**/*.php vendor/*.php vendor/**/*.php
	php build.php
# Developer Notices
## Javascript
### Preperation for building the project
Required Nodejs and npm to build.
Go to `__dev` and run `npm install` to install the needed packages. The Project uses webpack to build and deploy files.

### Build-commands
* `npm run dev` - build and watch files
* `npm run build` - build
* `npm run export` - build and deploys all plugin files to the svn target folder (defined in `developmentSettings.js`) 

## PHP-tests
There are some PHP-tests to ensure that saving works and no wrong values are stored in the attachement-meta-data.

### Installing the test-environment
Go to `__dev/test/php` and install the the test-environment with `composer install` (needs composer to be installed).

### Run the tests
Go to `__dev/test/php` and run the tests with `vendor/bin/phpunit`.
# Grocery List
Place this app in **nextcloud/apps/**

## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder

The make command will install or update Composer dependencies if a composer.json is present and also **npm run build** if a package.json is present. The npm **build** script should use local paths for build systems and package managers, so people that simply want to build the app won't need to install npm libraries globally, e.g.:

**package.json**:
```json
"scripts": {
    "test": "node node_modules/gulp-cli/bin/gulp.js karma",
    "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
    "build": "node node_modules/gulp-cli/bin/gulp.js"
}
```

## Demo data

To fill a test instance with some example lists (Supermarket, Discount Store, Drugstore, Farmers' Market) with categories and items, run:

    scripts/create-demo-data.py --url http://localhost/nc --user grocery --password grocery

The user has to exist already. Lists of that user with the same titles are replaced, other lists are kept. The screenshots in **screenshots/** show this data.

## Publish to App Store

Releases are built, signed and uploaded by the **appstore-build-publish** workflow when a GitHub release is published.

One-time setup:
1. Create a key and certificate request, keep `grocerylist.key` secret:

       openssl req -nodes -newkey rsa:4096 -keyout grocerylist.key -out grocerylist.csr -subj "/CN=grocerylist"

2. Open a pull request with `grocerylist.csr` at [nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests). The workflow downloads the signed `grocerylist.crt` from there.
3. Register the app on the [App Store](https://apps.nextcloud.com/developer/apps/new) with the certificate and the signature of the app id:

       echo -n "grocerylist" | openssl dgst -sha512 -sign grocerylist.key | openssl base64

4. Add the repository secrets `APP_PRIVATE_KEY` (content of `grocerylist.key`) and `APPSTORE_TOKEN` (from the App Store account settings).

For a release:
1. Set the new version in **appinfo/info.xml** and **package.json** and add it to **CHANGELOG.md**
2. Publish a GitHub release with the tag `v<version>`, e.g. `v0.1.0`

To only build the archive locally, run `make appstore`. It is written to **build/artifacts/grocerylist.tar.gz**.

## Translations

Translations are done by the Nextcloud community on [Transifex](https://app.transifex.com/nextcloud/nextcloud/) and synced nightly by `nextcloud-bot` into **l10n/**, see **.tx/config**. Do not edit the files in **l10n/**, they are overwritten by the sync.

**translationfiles/<lang>/grocerylist.po** hold initial translations (German and 20 further languages) for the initial upload to Transifex.

## Running tests
You can use the provided Makefile to run all tests by using:

    make test

This will run the PHP unit and integration tests and if a package.json is present in the **js/** folder will execute **npm run test**

Of course you can also install [PHPUnit](http://phpunit.de/getting-started.html) and use the configurations directly:

    phpunit -c phpunit.xml

or:

    phpunit -c phpunit.integration.xml

for integration tests

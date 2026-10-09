# Testing

## Unit tests
The folder `Test/Unit` contains PHPUnit unit tests for the controllers and the `ClassTypeOptions` view model.

## Integration tests
The folder `Test/Integration` contains Magento integration tests. They require `yireo/magento2-integration-test-helper`
and are run from the Magento root:

    cd dev/tests/integration
    ../../../vendor/bin/phpunit ../../../vendor/yireo/magento2-tax-class-manager/Test/Integration

The controller tests (`Test/Integration/Controller`) dispatch the real admin actions with an admin session and check
access control, rendered pages, redirects, session messages and whether tax classes were actually deleted.

## Playwright tests
The folder `Test/Playwright` contains Playwright tests for the admin grid and form. They are run with the Playwright
setup of the `loki/magento2-functional-tests` package:

    composer require loki/magento2-functional-tests
    bin/magento module:enable Loki_FunctionalTests
    bin/magento loki:functional-tests:modules:dump
    cd vendor/loki/magento2-functional-tests/Test/Playwright/
    npm install
    npx playwright test --project=Yireo_TaxClassManager

The tests log in to the Admin Panel via the login form and need the following environment variables (for instance in
`.env.playwright` in the Magento root; `prepare-playwright-tests.sh` regenerates that file, but appends
`.env.loki-functional-tests` to it, so that is the place for permanent values):

| Variable | Purpose |
|---|---|
| `TEST_URL` | Base URL of the shop |
| `ADMIN_USER` | Admin username, `ADMIN_USERNAME` also works (tests are skipped when missing) |
| `ADMIN_PASSWORD` | Admin password (tests are skipped when missing) |
| `ADMIN_PATH` | Backend frontName, defaults to `admin` |

The admin pages are opened via the admin menu and grid links, so the tests also work with "Add Secret Key to URLs"
enabled. Every test creates its own tax classes with unique names and removes them afterwards. The default tax class
"Retail Customer" is only used to check that a tax class in use can not be deleted.

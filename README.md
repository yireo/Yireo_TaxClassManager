# Yireo_TaxClassManager

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-tax-class-manager)

</div>
<!-- badges.specs.end -->


Manage the Magento tax classes (customer and product) in the Admin Panel, via a grid and a form built with Loki AdminComponents. You can find it under **Stores > Taxes > Tax Classes**.

## Permissions (ACL)
An admin role needs the following resources:

- **Stores > Taxes > Tax Classes > View Tax Classes** (`Yireo_TaxClassManager::tax_class_view`) to open the grid;
- **Stores > Taxes > Tax Classes > Save Tax Classes** (`Yireo_TaxClassManager::tax_class_save`) to create and edit tax classes;
- **Stores > Taxes > Tax Classes > Delete Tax Classes** (`Yireo_TaxClassManager::tax_class_delete`) to delete tax classes;
- **Loki Components** (`Loki_Components::index`), because the grid and the form load their data via the Loki Components AJAX endpoint.

Buttons and actions that a role is not allowed to use are hidden.

## Deleting tax classes
Magento refuses to delete a tax class that is used by tax rules, customer groups or products. On top of that, this module refuses to delete a tax class that is configured as the default product tax class or the default customer tax class (**Stores > Configuration > Sales > Tax > Tax Classes**) in any scope. A mass delete handles at most 500 tax classes at once.

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_TaxClassManager/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_TaxClassManager/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_TaxClassManager/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_TaxClassManager/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_TaxClassManager/compile.yml?label=compile)

</div>
<!-- badges.test.end -->

# Yireo_TaxClassManager

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

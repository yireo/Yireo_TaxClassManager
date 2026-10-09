import {
    test,
    expect,
    gotoGrid,
    resetGrid,
    searchGrid,
    waitForLokiPost,
    getRow,
    getRows,
    createTaxClass,
    deleteTaxClasses,
    uniqueName,
    GRID_PATH,
} from '@yireo-taxclassmanager/tax-class-manager';

test.describe('Tax class grid', function () {
    test.beforeEach(async function ({page}) {
        await resetGrid(page);
    });

    test('is reachable via the menu and shows the tax class columns', async function ({page}) {
        await expect(page).toHaveURL(new RegExp(GRID_PATH));
        await expect(page).toHaveTitle(/Tax Classes/);

        const headers = page.locator('table[data-role="grid"] thead th[data-column]');
        await expect(headers).toHaveText(['ID', 'Name', 'Type']);
        await expect(getRows(page).first()).toBeVisible();
    });

    test('shows readable type labels for the default tax classes', async function ({page}) {
        await searchGrid(page, 'Retail Customer');
        await expect(getRow(page, 'Retail Customer')).toContainText('Customer');

        await searchGrid(page, 'Taxable Goods');
        await expect(getRow(page, 'Taxable Goods')).toContainText('Product');
    });

    test('opens a new form via the "Add New Tax Class" button', async function ({page}) {
        await page.getByRole('button', {name: 'Add New Tax Class'}).click();

        await expect(page).toHaveTitle(/New Tax Class/);
        await expect(page.locator('input[data-name="class_name"]')).toHaveValue('');
    });

    test.describe('with custom tax classes', function () {
        const prefix = uniqueName('PW Grid');
        const customerClass = prefix + ' Customer';
        const productClass = prefix + ' Product';

        test.beforeEach(async function ({page}) {
            await createTaxClass(page, customerClass, 'CUSTOMER');
            await createTaxClass(page, productClass, 'PRODUCT');
            await gotoGrid(page);
        });

        test.afterEach(async function ({page}) {
            await deleteTaxClasses(page, [customerClass, productClass]);
        });

        test('searches tax classes by name', async function ({page}) {
            await searchGrid(page, prefix);

            await expect(getRows(page)).toHaveCount(2);
            await expect(getRow(page, customerClass)).toContainText('Customer');
            await expect(getRow(page, productClass)).toContainText('Product');

            await searchGrid(page, customerClass);
            await expect(getRows(page)).toHaveCount(1);
            await expect(getRow(page, customerClass)).toBeVisible();
        });

        test('filters tax classes by type', async function ({page}) {
            await searchGrid(page, prefix);

            await page.getByRole('button', {name: 'Filters'}).click();
            await page.locator('.admin__data-grid-filters select[data-name="class_type"]').selectOption('PRODUCT');

            const response = waitForLokiPost(page);
            await page.getByRole('button', {name: 'Apply Filters'}).click();
            await response;

            await expect(getRows(page)).toHaveCount(1);
            await expect(getRow(page, productClass)).toBeVisible();
            await expect(getRow(page, customerClass)).toHaveCount(0);
        });

        test('opens the edit form from a row', async function ({page}) {
            await searchGrid(page, customerClass);
            await getRow(page, customerClass).getByRole('link', {name: 'Edit'}).click();

            await expect(page).toHaveTitle(/Edit Tax Class/);
            await expect(page.locator('input[data-name="class_name"]')).toHaveValue(customerClass);
        });
    });
});

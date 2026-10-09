import {
    test,
    expect,
    gotoGrid,
    resetGrid,
    searchGrid,
    updateGrid,
    waitForGrid,
    waitForForm,
    getHeaders,
    setColumnActive,
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

        // The first visit stores the columns in a bookmark, so the reload shows the grid as it is on every later visit
        await page.reload();
        await waitForGrid(page);

        await expect(getHeaders(page)).toHaveText(['ID', 'Name', 'Type']);
        await expect(getRows(page).first()).toBeVisible();
        await expect(getRows(page).first().locator('td')).toHaveCount(5);
    });

    test.describe('with the columns selector', function () {
        test.afterEach(async function ({page}) {
            await gotoGrid(page);
            await setColumnActive(page, 'class_type', true);
        });

        test('hides a column, also after reloading the page', async function ({page}) {
            await setColumnActive(page, 'class_type', false);
            await expect(getHeaders(page)).toHaveText(['ID', 'Name']);

            await page.reload();
            await waitForGrid(page);

            await expect(getHeaders(page)).toHaveText(['ID', 'Name']);
            await expect(getRows(page).first().locator('td')).toHaveCount(4);

            await setColumnActive(page, 'class_type', true);
            await expect(getHeaders(page)).toHaveText(['ID', 'Name', 'Type']);
        });
    });

    test('shows readable type labels for the default tax classes', async function ({page}) {
        await searchGrid(page, 'Retail Customer');
        await expect(getRow(page, 'Retail Customer')).toContainText('Customer');

        await searchGrid(page, 'Taxable Goods');
        await expect(getRow(page, 'Taxable Goods')).toContainText('Product');
    });

    test('opens a new form via the "Add New Tax Class" button', async function ({page}) {
        await page.getByRole('button', {name: 'Add New Tax Class'}).click();
        await waitForForm(page);

        await expect(page).toHaveTitle(/New Tax Class/);
        await expect(page.locator('input[data-name="class_name"]')).toHaveValue('');
    });

    test.describe('with custom tax classes', function () {
        let prefix: string;
        let customerClass: string;
        let productClass: string;

        test.beforeEach(async function ({page}) {
            prefix = uniqueName('PW Grid');
            customerClass = prefix + ' Customer';
            productClass = prefix + ' Product';

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

            await updateGrid(page, async () => {
                await page.getByRole('button', {name: 'Apply Filters'}).click();
            });

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

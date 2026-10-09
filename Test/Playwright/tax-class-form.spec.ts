import {
    test,
    expect,
    gotoGrid,
    searchGrid,
    getRow,
    getRows,
    createTaxClass,
    openEditForm,
    deleteTaxClasses,
    fillName,
    clickButton,
    waitForForm,
    waitForLokiPost,
    uniqueName,
    GRID_PATH,
} from '@yireo-taxclassmanager/tax-class-manager';

test.describe('Tax class form', function () {
    const createdNames: string[] = [];

    test.afterEach(async function ({page}) {
        await deleteTaxClasses(page, createdNames.splice(0));
    });

    for (const [classType, label] of [['CUSTOMER', 'Customer'], ['PRODUCT', 'Product']] as const) {
        test(`creates a ${label.toLowerCase()} tax class`, async function ({page}) {
            const name = uniqueName(`PW ${label}`);
            createdNames.push(name);

            await createTaxClass(page, name, classType);

            await searchGrid(page, name);
            await expect(getRows(page)).toHaveCount(1);
            await expect(getRow(page, name)).toContainText(label);
        });
    }

    test('offers both class types and requires name and type', async function ({page}) {
        await gotoGrid(page);
        await clickButton(page, 'Add New Tax Class');
        await waitForForm(page);

        await expect(page.locator('input[data-name="class_name"]')).toHaveAttribute('required', '');
        const typeSelect = page.locator('select[data-name="class_type"]');
        await expect(typeSelect).toHaveAttribute('required', '');
        await expect(typeSelect.locator('option')).toHaveText(['-- Select a type --', 'Customer', 'Product']);

        const buttons = page.locator('.page-actions-buttons');
        await expect(buttons.getByRole('button', {name: 'Save & Close', exact: true})).toBeVisible();
        await expect(buttons.getByRole('button', {name: 'Delete', exact: true})).toHaveCount(0);
        await expect(buttons.getByRole('button', {name: 'Save & Continue', exact: true})).toHaveCount(0);
    });

    test('does not save a tax class without a name', async function ({page}) {
        await gotoGrid(page);
        await clickButton(page, 'Add New Tax Class');
        await waitForForm(page);
        await page.locator('select[data-name="class_type"]').selectOption('CUSTOMER');

        const response = waitForLokiPost(page);
        await clickButton(page, 'Save & Close');
        await response;

        await expect(page).not.toHaveURL(new RegExp(GRID_PATH));
        await expect(page.locator('input[data-name="class_name"]')).toBeVisible();
    });

    test.describe('editing an existing tax class', function () {
        let name: string;

        test.beforeEach(async function ({page}) {
            name = uniqueName('PW Edit');
            createdNames.push(name);
            await createTaxClass(page, name, 'PRODUCT');
            await openEditForm(page, name);
        });

        test('shows the type as read-only and offers the edit buttons', async function ({page}) {
            await expect(page).toHaveTitle(/Edit Tax Class/);
            await expect(page.locator('select[data-name="class_type"]')).toHaveCount(0);
            await expect(page.getByRole('main')).toContainText('PRODUCT');

            const buttons = page.locator('.page-actions-buttons');
            for (const label of ['Back', 'Save & Close', 'Delete', 'Save & Continue']) {
                await expect(buttons.getByRole('button', {name: label, exact: true})).toBeVisible();
            }
        });

        test('renames the tax class with "Save & Continue"', async function ({page}) {
            const newName = name + ' Renamed';
            createdNames.push(newName);

            await fillName(page, newName);
            const response = waitForLokiPost(page);
            await clickButton(page, 'Save & Continue');
            await response;

            await expect(page).toHaveTitle(/Edit Tax Class/);
            await page.reload();
            await expect(page.locator('input[data-name="class_name"]')).toHaveValue(newName);

            await gotoGrid(page);
            await searchGrid(page, newName);
            await expect(getRow(page, newName)).toContainText('Product');
        });

        test('returns to the grid with "Back" without saving', async function ({page}) {
            await fillName(page, name + ' Unsaved');
            await clickButton(page, 'Back');

            await expect(page).toHaveURL(new RegExp(GRID_PATH));
            await searchGrid(page, name);
            await expect(getRows(page)).toHaveCount(1);
            await expect(getRow(page, name + ' Unsaved')).toHaveCount(0);
        });
    });
});

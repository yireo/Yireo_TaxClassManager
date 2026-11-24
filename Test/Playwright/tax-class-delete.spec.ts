import {
    test,
    expect,
    gotoGrid,
    resetGrid,
    searchGrid,
    getRow,
    getRows,
    getRowId,
    createTaxClass,
    openEditForm,
    deleteTaxClasses,
    clickRowDelete,
    clickButton,
    expectMessage,
    uniqueName,
    GRID_PATH,
} from '@yireo-taxclassmanager/tax-class-manager';

test.describe('Deleting tax classes', function () {
    const createdNames: string[] = [];

    test.afterEach(async function ({page}) {
        await deleteTaxClasses(page, createdNames.splice(0));
    });

    test('deletes a tax class via the row action', async function ({page}) {
        const name = uniqueName('PW Row Delete');
        createdNames.push(name);
        await createTaxClass(page, name, 'CUSTOMER');

        await searchGrid(page, name);
        const deleteRequest = page.waitForRequest(request => request.url().includes('taxclass/delete'));
        await clickRowDelete(page, name);

        const request = await deleteRequest;
        expect(request.method()).toBe('POST');
        expect(request.postData()).toContain('form_key=');

        await expect(page).toHaveURL(new RegExp(GRID_PATH));
        await expectMessage(page, 'success', 'The tax class has been deleted.');
        await expect(getRow(page, name)).toHaveCount(0);
    });

    test('keeps a tax class when the row delete is not confirmed', async function ({page}) {
        const name = uniqueName('PW Row Delete Cancel');
        createdNames.push(name);
        await createTaxClass(page, name, 'CUSTOMER');

        await searchGrid(page, name);
        await clickRowDelete(page, name, false);

        await expect(page).toHaveURL(new RegExp(GRID_PATH));
        await expect(getRow(page, name)).toHaveCount(1);
    });

    test('deletes a tax class from the edit form', async function ({page}) {
        const name = uniqueName('PW Form Delete');
        createdNames.push(name);
        await createTaxClass(page, name, 'PRODUCT');
        await openEditForm(page, name);

        await clickButton(page, 'Delete');
        await expect(page).toHaveURL(new RegExp(GRID_PATH));

        await expect(async () => {
            await gotoGrid(page);
            await expect(getRow(page, name)).toHaveCount(0);
        }).toPass();
    });

    test('deletes multiple tax classes via the mass action', async function ({page}) {
        const prefix = uniqueName('PW Mass Delete');
        const names = [prefix + ' A', prefix + ' B'];
        createdNames.push(...names);
        for (const name of names) {
            await createTaxClass(page, name, 'CUSTOMER');
        }

        await searchGrid(page, prefix);
        await expect(getRows(page)).toHaveCount(2);
        for (const name of names) {
            await getRow(page, name).locator('input[data-row-id]').check();
        }

        await expect(page.getByText(/records found/)).toContainText('(2 selected)');
        await page.locator('button[title="Select Items"]').click();
        await page.locator('.action-menu-items').getByText('Delete', {exact: true}).click();

        await expect(page).toHaveURL(new RegExp(GRID_PATH));
        await expectMessage(page, 'success', '2 tax class(es) have been deleted.');
        await searchGrid(page, prefix);
        await expect(getRows(page)).toHaveCount(0);
    });

    test('refuses to delete a tax class that is still in use', async function ({page}) {
        await resetGrid(page);
        await searchGrid(page, 'Retail Customer');
        const rowId = await getRowId(page, 'Retail Customer');

        await clickRowDelete(page, 'Retail Customer');

        await expect(page).toHaveURL(new RegExp(GRID_PATH));
        // Retail Customer is the default customer tax class; Magento also refuses because of tax rules or customer groups
        await expectMessage(page, 'error', /You cannot delete this tax class because it is used in/);

        await searchGrid(page, 'Retail Customer');
        await expect(page.locator(`input[data-row-id="${rowId}"]`)).toHaveCount(1);
    });
});

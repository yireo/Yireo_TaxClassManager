import {test as baseTest, expect, Locator, Page} from '@playwright/test';

export const GRID_PATH = 'tax_class_manager/taxclass/grid';

const LOKI_POST_URL = 'loki_components/index/html';

const env = (name: string): string => (process.env[name] ?? '').trim();

export const adminPath = (): string => env('ADMIN_PATH').replace(/^\/+|\/+$/g, '') || 'admin';

const adminUsername = (): string => env('ADMIN_USERNAME') || env('ADMIN_USER');

export const uniqueName = (prefix: string = 'PW Tax Class'): string =>
    `${prefix} ${Date.now()}-${Math.floor(Math.random() * 10000)}`;

/**
 * Log in to the Magento Admin Panel via its login form, using ADMIN_USERNAME and ADMIN_PASSWORD
 */
export async function loginAsAdmin(page: Page) {
    await page.goto('/' + adminPath() + '/');

    const usernameField = page.locator('#username');
    if (await usernameField.count() === 0) {
        return;
    }

    await usernameField.fill(adminUsername());
    await page.locator('#login').fill(env('ADMIN_PASSWORD'));
    await page.locator('.action-login').click();
    await expect(page.locator('#username'), 'Admin login failed').toHaveCount(0);
}

/**
 * Wait until the Alpine component containing the element is initialized; interacting with the element earlier is lost,
 * because Alpine then (re)binds the element to the initial component state
 */
export async function waitForAlpineComponent(page: Page, selector: string) {
    await page.waitForFunction((selector: string) => {
        const element = document.querySelector(selector)?.closest('[x-data]') as any;
        return !!element && Array.isArray(element._x_dataStack);
    }, selector);
}

/**
 * Open the tax class grid via the admin menu, so that this works with "Add Secret Key to URLs" enabled
 */
export async function gotoGrid(page: Page) {
    if (!page.url().includes('/' + adminPath() + '/')) {
        await loginAsAdmin(page);
    }

    const menuLink = page.locator(`a[href*="${GRID_PATH}"]`).first();
    await expect(menuLink, 'Menu item "Tax Classes" not found').toBeAttached();
    await page.goto((await menuLink.getAttribute('href'))!);
    await expect(page.locator('table[data-role="grid"]')).toBeVisible();
    await waitForAlpineComponent(page, 'table[data-role="grid"]');
}

export async function waitForForm(page: Page) {
    await expect(page.locator('input[data-name="class_name"]')).toBeVisible();
    await waitForAlpineComponent(page, 'input[data-name="class_name"]');
}

export function waitForLokiPost(page: Page) {
    return page.waitForResponse(response => response.url().includes(LOKI_POST_URL));
}

export function getRows(page: Page): Locator {
    return page.locator('table[data-role="grid"] tbody tr.data-row');
}

export function getRow(page: Page, name: string): Locator {
    return getRows(page).filter({hasText: name});
}

export async function getRowId(page: Page, name: string): Promise<string> {
    const rowId = await getRow(page, name).locator('input[data-row-id]').getAttribute('data-row-id');
    expect(rowId, `No row ID found for "${name}"`).toBeTruthy();

    return rowId!;
}

/**
 * Search the grid; an empty term resets the search (the search is stored in the admin session)
 */
export async function searchGrid(page: Page, term: string) {
    const searchField = page.locator('#grid-search');
    if (await searchField.inputValue() === term) {
        return;
    }

    const response = waitForLokiPost(page);
    await searchField.fill(term);
    await searchField.dispatchEvent('change');
    await response;
}

export async function clearGridFilters(page: Page) {
    const clearAll = page.getByRole('button', {name: 'Clear all'});
    if (await clearAll.count() === 0) {
        return;
    }

    const response = waitForLokiPost(page);
    await clearAll.evaluate((element: HTMLElement) => element.click());
    await response;
}

export async function resetGrid(page: Page) {
    await gotoGrid(page);
    await searchGrid(page, '');
    await clearGridFilters(page);
}

export async function fillName(page: Page, name: string) {
    const nameField = page.locator('input[data-name="class_name"]');
    await nameField.fill(name);
    await nameField.dispatchEvent('change');
}

export async function clickButton(page: Page, label: string) {
    await page.locator('.page-actions-buttons').getByRole('button', {name: label, exact: true}).click();
}

/**
 * Create a new tax class through the form, ending up on the grid again
 */
export async function createTaxClass(page: Page, name: string, classType: 'CUSTOMER' | 'PRODUCT') {
    await gotoGrid(page);
    await clickButton(page, 'Add New Tax Class');
    await waitForForm(page);

    await fillName(page, name);
    await page.locator('select[data-name="class_type"]').selectOption(classType);
    await clickButton(page, 'Save & Close');

    await expect(page).toHaveURL(new RegExp(GRID_PATH));
}

export async function openEditForm(page: Page, name: string) {
    await gotoGrid(page);
    await searchGrid(page, name);
    await getRow(page, name).getByRole('link', {name: 'Edit'}).click();
    await waitForForm(page);
    await expect(page.locator('input[data-name="class_name"]')).toHaveValue(name);
}

/**
 * Remove tax classes that were created by a test, ignoring classes that are already gone
 */
export async function deleteTaxClasses(page: Page, names: string[]) {
    for (const name of names) {
        await gotoGrid(page);
        await searchGrid(page, name);

        const deleteLink = getRow(page, name).getByRole('link', {name: 'Delete'});
        if (await deleteLink.count() > 0) {
            await deleteLink.click();
            await expect(page).toHaveURL(new RegExp(GRID_PATH));
        }
    }

    await resetGrid(page);
}

export async function expectMessage(page: Page, type: 'success' | 'error', text: string | RegExp) {
    await expect(page.locator(`.message-${type}`).filter({hasText: text}).first()).toBeVisible();
}

/**
 * Playwright test with a page that is logged in to the Admin Panel; tests are skipped when the credentials are missing
 */
export const test = baseTest.extend<{}>({
    page: async ({page}, use, testInfo) => {
        const missing = [];
        if (!adminUsername()) missing.push('ADMIN_USER');
        if (!env('ADMIN_PASSWORD')) missing.push('ADMIN_PASSWORD');
        testInfo.skip(missing.length > 0, `Missing environment variable(s): ${missing.join(', ')}`);

        await loginAsAdmin(page);
        await use(page);
    },
});

export {expect};

const { test, expect } = require('@playwright/test');

async function login(page, subject) {
  await page.goto('/civicrm/oidc/login');
  await expect(page).toHaveURL(/https:\/\/oidc:8080\/e2e\/authorize/, { timeout: 30000 });
  await page.locator('input[name="username"]').fill(subject);
  await page.locator('button[type="submit"], input[type="submit"]').first().click();
}

async function expectCiviHome(page) {
  await expect(page).toHaveURL(/https:\/\/civicrm\/civicrm\/home/, { timeout: 30000 });
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('body')).toContainText('CiviCRM', { timeout: 30000 });
}

async function expectLoginFailure(page) {
  await expect(page).toHaveURL(/https:\/\/civicrm\/civicrm\/login/, { timeout: 30000 });
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('body')).toContainText('OpenID Connect login failed', { timeout: 30000 });
}

test('provisions, reuses and deauthorizes an OIDC identity', async ({ browser }) => {
  const authorized = await browser.newContext({ ignoreHTTPSErrors: true });
  const firstPage = await authorized.newPage();
  await login(firstPage, 'staff');
  await expectCiviHome(firstPage);
  await authorized.close();

  const deauthorized = await browser.newContext({ ignoreHTTPSErrors: true });
  const secondPage = await deauthorized.newPage();
  await login(secondPage, 'staff-without-role');
  await expectLoginFailure(secondPage);
  await deauthorized.close();

  const denied = await browser.newContext({ ignoreHTTPSErrors: true });
  const deniedPage = await denied.newPage();
  await login(deniedPage, 'unmapped');
  await expectLoginFailure(deniedPage);
  await denied.close();

  const migrated = await browser.newContext({ ignoreHTTPSErrors: true });
  const migratedPage = await migrated.newPage();
  await login(migratedPage, 'migrated');
  await expectCiviHome(migratedPage);
  await migrated.close();

  const migratedDeauthorized = await browser.newContext({ ignoreHTTPSErrors: true });
  const migratedDeniedPage = await migratedDeauthorized.newPage();
  await login(migratedDeniedPage, 'migrated-without-role');
  await expectLoginFailure(migratedDeniedPage);
  await migratedDeauthorized.close();
});

const { test, expect } = require('@playwright/test');

async function login(page, subject) {
  await page.goto('/civicrm/oidc/login');
  await expect(page).toHaveURL(/https:\/\/oidc:8080\/e2e\/authorize/);
  await page.locator('input[name="username"]').fill(subject);
  await page.locator('button[type="submit"], input[type="submit"]').first().click();
}

test('provisions, reuses and deauthorizes an OIDC identity', async ({ browser }) => {
  const authorized = await browser.newContext({ ignoreHTTPSErrors: true });
  const firstPage = await authorized.newPage();
  await login(firstPage, 'staff');
  await expect(firstPage).toHaveURL(/https:\/\/civicrm\/civicrm\/home/);
  await expect(firstPage.locator('body')).toContainText('CiviCRM');
  await authorized.close();

  const deauthorized = await browser.newContext({ ignoreHTTPSErrors: true });
  const secondPage = await deauthorized.newPage();
  await login(secondPage, 'staff-without-role');
  await expect(secondPage).toHaveURL(/https:\/\/civicrm\/civicrm\/login/);
  await expect(secondPage.locator('body')).toContainText('OpenID Connect login failed');
  await deauthorized.close();

  const denied = await browser.newContext({ ignoreHTTPSErrors: true });
  const deniedPage = await denied.newPage();
  await login(deniedPage, 'unmapped');
  await expect(deniedPage).toHaveURL(/https:\/\/civicrm\/civicrm\/login/);
  await expect(deniedPage.locator('body')).toContainText('OpenID Connect login failed');
  await denied.close();
});

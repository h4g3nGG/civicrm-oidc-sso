const { test, expect } = require('@playwright/test');

test('authenticates through a real authentik OpenID Provider', async ({ page }) => {
  await page.goto('/civicrm/oidc/login');
  await expect(page).toHaveURL(/https:\/\/authentik\/if\/flow\//, { timeout: 30000 });

  await page.getByRole('textbox', { name: /email|username/i }).fill('e2e.staff');
  await page.getByRole('button', { name: /log in|continue/i }).click();
  await page.getByLabel(/password/i).fill('e2e-authentik-password');
  await page.getByRole('button', { name: /log in|continue/i }).click();

  await expect(page).toHaveURL(/https:\/\/civicrm\/civicrm\/home/, { timeout: 30000 });
  await expect(page.locator('body')).toContainText('CiviCRM');
});

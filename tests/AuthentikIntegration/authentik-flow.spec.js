const { test, expect } = require('@playwright/test');

function sanitizedUrl(rawUrl) {
  const url = new URL(rawUrl);
  const queryKeys = [...new Set(url.searchParams.keys())].sort();
  return `${url.origin}${url.pathname}${queryKeys.length ? `?${queryKeys.join('&')}` : ''}`;
}

function oauthErrorFrom(rawUrl) {
  const url = new URL(rawUrl);
  if (!url.searchParams.has('error')) {
    return null;
  }
  return {
    error: url.searchParams.get('error'),
    description: url.searchParams.get('error_description'),
  };
}

test('authenticates through a real authentik OpenID Provider', async ({ page }) => {
  const response = await page.goto('/civicrm/oidc/login');
  const redirects = [];
  let oauthError = null;
  for (let request = response.request(); request; request = request.redirectedFrom()) {
    redirects.unshift(sanitizedUrl(request.url()));
    oauthError ??= oauthErrorFrom(request.url());
  }
  console.log(`Sanitized redirect chain: ${redirects.join(' -> ')}`);
  if (oauthError) {
    console.log(`Synthetic provider error: ${JSON.stringify(oauthError)}`);
  }
  await expect(page).toHaveURL(/https:\/\/authentik\/if\/flow\//, { timeout: 30000 });

  await page.getByRole('textbox', { name: /email|username/i }).fill('e2e.staff');
  await page.getByRole('button', { name: /log in|continue/i }).click();
  await page.getByLabel(/password/i).fill('e2e-authentik-password');
  await page.getByRole('button', { name: /log in|continue/i }).click();

  await expect(page).toHaveURL(/https:\/\/civicrm\/civicrm\/home/, { timeout: 30000 });
  await expect(page.locator('body')).toContainText('CiviCRM');
});

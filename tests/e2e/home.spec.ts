import { test, expect } from '@playwright/test';

test('health endpoint responds', async ({ page }) => {
  const response = await page.goto('/healthz.php');
  expect(response?.ok()).toBeTruthy();
  await expect(page.locator('body')).toBeVisible();
});



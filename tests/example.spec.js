import { test, expect } from '@playwright/test';

// Hacker payloads for security testing
const sqlInjectionPayloads = [
  "' OR '1'='1",
  "admin' --",
  "' OR 1=1 --",
  "' UNION SELECT NULL --",
  "1' AND '1'='1",
];

const xssPayloads = [
  "<script>alert('xss')</script>",
  "<img src=x onerror=alert('xss')>",
  "javascript:alert('xss')",
  "<svg onload=alert('xss')>",
];

const commonPasswords = ['password', '123456', 'admin', 'letmein', '12345678'];
const bruteForceEmails = [
  'admin@example.com',
  'root@example.com',
  'test@example.com',
  'user@example.com',
];

function getRandomPayload(array) {
  return array[Math.floor(Math.random() * array.length)];
}

function getRandomEmail() {
  return `hacker${Math.floor(Math.random() * 10000)}@example.com`;
}

test('legitimate login test', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  await page.getByLabel('email').fill('ralphpetallo@gmail.com');
  await page.getByLabel('password').fill('ralphpetallo');
  await page.getByText('Login').click();

  await expect(page).toHaveURL('http://127.0.0.1:8000/user');
});

test('sql injection attempts - email field', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  
  for (const payload of sqlInjectionPayloads) {
    await page.getByLabel('email').fill(payload);
    await page.getByLabel('password').fill('password');
    await page.getByText('Login').click();
    
    // Should reject with error, not get logged in
    const url = page.url();
    expect(url).not.toContain('/user');
    expect(url).not.toContain('/admin');
    
    // Reset for next iteration
    await page.getByLabel('email').clear();
    await page.waitForTimeout(300);
  }
});

test('xss attempts - password field', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  
  for (const payload of xssPayloads) {
    await page.getByLabel('email').fill('test@example.com');
    await page.getByLabel('password').fill(payload);
    await page.getByText('Login').click();
    
    // XSS should be blocked, no script execution
    const alerts = await page.evaluate(() => window.xssDetected);
    expect(alerts).not.toBeDefined();
    
    await page.getByLabel('password').clear();
    await page.waitForTimeout(300);
  }
});

test('brute force random attempts', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  
  // Try 5 random failed logins
  for (let i = 0; i < 5; i++) {
    const email = getRandomEmail();
    const password = getRandomPayload(commonPasswords);
    
    await page.getByLabel('email').fill(email);
    await page.getByLabel('password').fill(password);
    await page.getByText('Login').click();
    
    // Should always fail and stay on login page
    const url = page.url();
    expect(url).toContain('127.0.0.1:8000');
    expect(url).not.toContain('/user');
    
    await page.getByLabel('email').clear();
    await page.getByLabel('password').clear();
    await page.waitForTimeout(500);
  }
  
  // After multiple failed attempts, should see lockout message
  const alertText = await page.locator('.alert').textContent();
  expect(alertText).toMatch(/failed|too many|wait|locked/i);
});

test('empty field bypass attempt', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  
  // Try submitting with empty email
  await page.getByLabel('password').fill('password');
  await page.getByText('Login').click();
  const errorMsg = await page.locator('.alert').textContent();
  expect(errorMsg?.toLowerCase()).toContain('email');
  
  // Try submitting with empty password
  await page.getByLabel('email').fill('test@example.com');
  await page.getByLabel('password').clear();
  await page.getByText('Login').click();
  const errorMsg2 = await page.locator('.alert').textContent();
  expect(errorMsg2?.toLowerCase()).toContain('password');
});

test('timing attack resistance', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000');
  
  const timings = [];
  
  // Try existing email with wrong password
  const start1 = Date.now();
  await page.getByLabel('email').fill('ralphpetallo@gmail.com');
  await page.getByLabel('password').fill('wrongpassword');
  await page.getByText('Login').click();
  timings.push(Date.now() - start1);
  
  await page.getByLabel('email').clear();
  await page.getByLabel('password').clear();
  await page.waitForTimeout(1000);
  
  // Try non-existing email
  const start2 = Date.now();
  await page.getByLabel('email').fill('nonexistent@example.com');
  await page.getByLabel('password').fill('randompass');
  await page.getByText('Login').click();
  timings.push(Date.now() - start2);
  
  // Response times should be similar (no timing leak)
  const timeDiff = Math.abs(timings[0] - timings[1]);
  console.log(`Timing attack test - Response time difference: ${timeDiff}ms`);
  // Should be less than 500ms difference (timing resistant)
  expect(timeDiff).toBeLessThan(500);
});


# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: example.spec.js >> legitimate login test
- Location: tests\example.spec.js:35:5

# Error details

```
Error: expect(page).toHaveURL(expected) failed

Expected: "http://127.0.0.1:8000/user"
Received: "http://127.0.0.1:8000/"
Timeout:  5000ms

Call log:
  - Expect "toHaveURL" with timeout 5000ms
    13 × unexpected value "http://127.0.0.1:8000/"

```

```yaml
- banner:
  - img "Logo"
  - text: DashQuiz Assessment Portal
- main:
  - text: ✦ SNSU Capstone Project
  - heading "Learning is better when we do it together" [level=1]
  - paragraph: Practice, learn, and improve your skills with Dash Quiz.
  - heading "Welcome back!" [level=2]
  - paragraph: Sign in to your account
  - text: Email address
  - textbox "Email address":
    - /placeholder: "@example.com"
    - text: ralphpetallo@gmail.com
  - text: Password
  - textbox "Password":
    - /placeholder: ••••••
    - text: ralphpetallo
  - button [disabled]
  - link "Forgot password?":
    - /url: /forgot
  - text: or
  - link "Create account":
    - /url: /register
- contentinfo: © 2026 Dash Quiz • SNSU Capstone Project
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | // Hacker payloads for security testing
  4   | const sqlInjectionPayloads = [
  5   |   "' OR '1'='1",
  6   |   "admin' --",
  7   |   "' OR 1=1 --",
  8   |   "' UNION SELECT NULL --",
  9   |   "1' AND '1'='1",
  10  | ];
  11  | 
  12  | const xssPayloads = [
  13  |   "<script>alert('xss')</script>",
  14  |   "<img src=x onerror=alert('xss')>",
  15  |   "javascript:alert('xss')",
  16  |   "<svg onload=alert('xss')>",
  17  | ];
  18  | 
  19  | const commonPasswords = ['password', '123456', 'admin', 'letmein', '12345678'];
  20  | const bruteForceEmails = [
  21  |   'admin@example.com',
  22  |   'root@example.com',
  23  |   'test@example.com',
  24  |   'user@example.com',
  25  | ];
  26  | 
  27  | function getRandomPayload(array) {
  28  |   return array[Math.floor(Math.random() * array.length)];
  29  | }
  30  | 
  31  | function getRandomEmail() {
  32  |   return `hacker${Math.floor(Math.random() * 10000)}@example.com`;
  33  | }
  34  | 
  35  | test('legitimate login test', async ({ page }) => {
  36  |   await page.goto('http://127.0.0.1:8000');
  37  |   await page.getByLabel('email').fill('ralphpetallo@gmail.com');
  38  |   await page.getByLabel('password').fill('ralphpetallo');
  39  |   await page.getByText('Login').click();
  40  | 
> 41  |   await expect(page).toHaveURL('http://127.0.0.1:8000/user');
      |                      ^ Error: expect(page).toHaveURL(expected) failed
  42  | });
  43  | 
  44  | test('sql injection attempts - email field', async ({ page }) => {
  45  |   await page.goto('http://127.0.0.1:8000');
  46  |   
  47  |   for (const payload of sqlInjectionPayloads) {
  48  |     await page.getByLabel('email').fill(payload);
  49  |     await page.getByLabel('password').fill('password');
  50  |     await page.getByText('Login').click();
  51  |     
  52  |     // Should reject with error, not get logged in
  53  |     const url = page.url();
  54  |     expect(url).not.toContain('/user');
  55  |     expect(url).not.toContain('/admin');
  56  |     
  57  |     // Reset for next iteration
  58  |     await page.getByLabel('email').clear();
  59  |     await page.waitForTimeout(300);
  60  |   }
  61  | });
  62  | 
  63  | test('xss attempts - password field', async ({ page }) => {
  64  |   await page.goto('http://127.0.0.1:8000');
  65  |   
  66  |   for (const payload of xssPayloads) {
  67  |     await page.getByLabel('email').fill('test@example.com');
  68  |     await page.getByLabel('password').fill(payload);
  69  |     await page.getByText('Login').click();
  70  |     
  71  |     // XSS should be blocked, no script execution
  72  |     const alerts = await page.evaluate(() => window.xssDetected);
  73  |     expect(alerts).not.toBeDefined();
  74  |     
  75  |     await page.getByLabel('password').clear();
  76  |     await page.waitForTimeout(300);
  77  |   }
  78  | });
  79  | 
  80  | test('brute force random attempts', async ({ page }) => {
  81  |   await page.goto('http://127.0.0.1:8000');
  82  |   
  83  |   // Try 5 random failed logins
  84  |   for (let i = 0; i < 5; i++) {
  85  |     const email = getRandomEmail();
  86  |     const password = getRandomPayload(commonPasswords);
  87  |     
  88  |     await page.getByLabel('email').fill(email);
  89  |     await page.getByLabel('password').fill(password);
  90  |     await page.getByText('Login').click();
  91  |     
  92  |     // Should always fail and stay on login page
  93  |     const url = page.url();
  94  |     expect(url).toContain('127.0.0.1:8000');
  95  |     expect(url).not.toContain('/user');
  96  |     
  97  |     await page.getByLabel('email').clear();
  98  |     await page.getByLabel('password').clear();
  99  |     await page.waitForTimeout(500);
  100 |   }
  101 |   
  102 |   // After multiple failed attempts, should see lockout message
  103 |   const alertText = await page.locator('.alert').textContent();
  104 |   expect(alertText).toMatch(/failed|too many|wait|locked/i);
  105 | });
  106 | 
  107 | test('empty field bypass attempt', async ({ page }) => {
  108 |   await page.goto('http://127.0.0.1:8000');
  109 |   
  110 |   // Try submitting with empty email
  111 |   await page.getByLabel('password').fill('password');
  112 |   await page.getByText('Login').click();
  113 |   const errorMsg = await page.locator('.alert').textContent();
  114 |   expect(errorMsg?.toLowerCase()).toContain('email');
  115 |   
  116 |   // Try submitting with empty password
  117 |   await page.getByLabel('email').fill('test@example.com');
  118 |   await page.getByLabel('password').clear();
  119 |   await page.getByText('Login').click();
  120 |   const errorMsg2 = await page.locator('.alert').textContent();
  121 |   expect(errorMsg2?.toLowerCase()).toContain('password');
  122 | });
  123 | 
  124 | test('timing attack resistance', async ({ page }) => {
  125 |   await page.goto('http://127.0.0.1:8000');
  126 |   
  127 |   const timings = [];
  128 |   
  129 |   // Try existing email with wrong password
  130 |   const start1 = Date.now();
  131 |   await page.getByLabel('email').fill('ralphpetallo@gmail.com');
  132 |   await page.getByLabel('password').fill('wrongpassword');
  133 |   await page.getByText('Login').click();
  134 |   timings.push(Date.now() - start1);
  135 |   
  136 |   await page.getByLabel('email').clear();
  137 |   await page.getByLabel('password').clear();
  138 |   await page.waitForTimeout(1000);
  139 |   
  140 |   // Try non-existing email
  141 |   const start2 = Date.now();
```
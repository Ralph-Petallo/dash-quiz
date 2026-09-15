# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: example.spec.js >> timing attack resistance
- Location: tests\example.spec.js:124:5

# Error details

```
Error: expect(received).toBeLessThan(expected)

Expected: < 500
Received:   805
```

# Page snapshot

```yaml
- generic [ref=e4]:
  - banner [ref=e5]:
    - generic [ref=e6]:
      - generic [ref=e7]:
        - img "Logo" [ref=e8]
        - generic [ref=e9]: DashQuiz
      - generic [ref=e10]: Assessment Portal
  - main [ref=e11]:
    - generic [ref=e12]:
      - generic [ref=e13]: ✦ SNSU Capstone Project
      - heading "Learning is better when we do it together" [level=1] [ref=e14]:
        - text: Learning is better
        - text: when we do it together
      - paragraph [ref=e15]: Practice, learn, and improve your skills with Dash Quiz.
    - generic [ref=e17]:
      - heading "Welcome back!" [level=2] [ref=e18]
      - paragraph [ref=e19]: Sign in to your account
      - generic [ref=e20]:
        - generic [ref=e21]: Email address
        - textbox "Email address" [ref=e22]:
          - /placeholder: "@example.com"
          - text: nonexistent@example.com
      - generic [ref=e23]:
        - generic [ref=e24]: Password
        - textbox "Password" [ref=e25]:
          - /placeholder: ••••••
          - text: randompass
      - button [disabled] [ref=e26]
      - generic [ref=e28]:
        - link "Forgot password?" [ref=e30] [cursor=pointer]:
          - /url: /forgot
        - generic [ref=e31]: or
        - link "Create account" [ref=e34] [cursor=pointer]:
          - /url: /register
  - contentinfo [ref=e35]: © 2026 Dash Quiz • SNSU Capstone Project
```

# Test source

```ts
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
  142 |   await page.getByLabel('email').fill('nonexistent@example.com');
  143 |   await page.getByLabel('password').fill('randompass');
  144 |   await page.getByText('Login').click();
  145 |   timings.push(Date.now() - start2);
  146 |   
  147 |   // Response times should be similar (no timing leak)
  148 |   const timeDiff = Math.abs(timings[0] - timings[1]);
  149 |   console.log(`Timing attack test - Response time difference: ${timeDiff}ms`);
  150 |   // Should be less than 500ms difference (timing resistant)
> 151 |   expect(timeDiff).toBeLessThan(500);
      |                    ^ Error: expect(received).toBeLessThan(expected)
  152 | });
  153 | 
  154 | 
```
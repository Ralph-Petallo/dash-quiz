<template>
    <div class="legal-page">
        <header class="legal-header">
            <div class="header-content">
                <router-link to="/" class="back-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Back to login
                </router-link>

                <span class="eyebrow">CSS PREP</span>
                <h1>Privacy Policy</h1>
                <p>
                    This explains what information CSS Prep collects about you, why we collect it,
                    and who can see it.
                </p>
                <span class="updated">Last updated: {{ lastUpdated }}</span>
            </div>
        </header>

        <main class="legal-content">
            <section class="legal-section">
                <span class="section-number">01</span>
                <div>
                    <h2>Who we are</h2>
                    <p>
                        CSS Prep is a practice and assessment website for Grade 12 Computer Systems Servicing
                        learners at Taft High School. It was made by Ralph C. Petallo, a student of Surigao del
                        Norte State University, as a capstone project.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">02</span>
                <div>
                    <h2>What we collect</h2>
                    <ul>
                        <li><strong>Your account:</strong> your first name, last name, and email address.</li>
                        <li><strong>Your results:</strong> the assessments you take, your scores, and the dates you took
                            them.</li>
                        <li><strong>Your profile photo:</strong> only if you upload one.</li>
                    </ul>
                    <p>If you sign in with Google, we get your name, email, and photo from Google. We never see your
                        Google password.</p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">03</span>
                <div>
                    <h2>Why we collect it</h2>
                    <ul>
                        <li>To let you sign in and keep your account safe.</li>
                        <li>To score your assessments and show your results and progress.</li>
                        <li>To let your teacher see your results and help you improve.</li>
                        <li>To send you emails such as a confirmation link or a password reset.</li>
                    </ul>
                    <p>We do not sell your information and we do not show ads.</p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">04</span>
                <div>
                    <h2>Who can see your information</h2>
                    <ul>
                        <li><strong>You</strong> can see your own results and profile.</li>
                        <li><strong>Your teacher and the system administrator</strong> can see your results, for school
                            use.</li>
                        <li><strong>Other learners</strong> can see your name, photo, and best score on the ranking of
                            an assessment. They cannot see your answers or your other results.</li>
                    </ul>
                    <p>If you do not want your name or photo on the ranking, tell your teacher before you take an
                        assessment.</p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">05</span>
                <div>
                    <h2>How long we keep it</h2>
                    <ul>
                        <li><strong>Your account and results</strong> are kept for one school year, then deleted or made
                            anonymous.</li>
                        <li><strong>Research survey answers</strong> are deleted 7 days after they are collected. Only
                            group totals are kept.</li>
                    </ul>
                    <p>You can delete your account anytime from your Profile page. This removes your profile and your
                        results permanently.</p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">06</span>
                <div>
                    <h2>Keeping you signed in</h2>
                    <p>
                        CSS Prep saves a few things in your browser so you stay signed in and pages load faster.
                        We do not use these to track you or show you ads. You can clear them anytime from your
                        browser settings, but you will need to sign in again.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">07</span>
                <div>
                    <h2>Your rights</h2>
                    <p>Under the Philippine Data Privacy Act of 2012, you can:</p>
                    <ul>
                        <li>Ask what information we have about you.</li>
                        <li>Ask us to correct wrong information.</li>
                        <li>Ask us to delete your information.</li>
                        <li>Complain to the National Privacy Commission at <a href="https://privacy.gov.ph"
                                target="_blank" rel="noopener">privacy.gov.ph</a>.</li>
                    </ul>
                    <p>
                        To ask about any of these, email <a
                            href="mailto:ralphpetallo@gmail.com">ralphpetallo@gmail.com</a>
                        from the email on your account.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">08</span>
                <div>
                    <h2>Learners under 18</h2>
                    <p>
                        Many learners are under 18. If you are, please ask your parent or guardian to read this
                        policy with you.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">09</span>
                <div>
                    <h2>Contact</h2>
                    <p>
                        Ralph C. Petallo<br />
                        <a href="mailto:ralphpetallo@gmail.com">ralphpetallo@gmail.com</a>
                    </p>
                </div>
            </section>

            <div class="bottom-back">
                <router-link to="/" class="back-button">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Back to login
                </router-link>
            </div>
        </main>
    </div>
</template>

<script setup>
const lastUpdated = 'October 2026'
</script>

<style scoped>
.legal-page {
    min-height: 100%;
    background: #fafafa;
    color: #111111;
}

.legal-header {
    padding: 40px 32px 36px;
    border-bottom: 1px solid #e5e5e5;
    background: #ffffff;
}

.header-content {
    max-width: 900px;
    margin: 0 auto;
}

.back-link,
.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111111;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.back-link {
    margin-bottom: 28px;
}

.back-link:hover,
.back-button:hover {
    text-decoration: underline;
}

.eyebrow {
    display: block;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    color: #737373;
}

.legal-header h1 {
    margin: 0;
    font-size: clamp(30px, 4vw, 44px);
    line-height: 1.05;
    font-weight: 700;
    letter-spacing: -0.04em;
}

.legal-header p {
    max-width: 640px;
    margin: 14px 0 10px;
    font-size: 15px;
    line-height: 1.7;
    color: #737373;
}

.updated {
    font-size: 11px;
    color: #a3a3a3;
}

.legal-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 32px 72px;
}

.legal-section {
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 20px;
    padding: 26px 0;
    border-bottom: 1px solid #e5e5e5;
}

.legal-section:first-child {
    padding-top: 0;
}

.section-number {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    background: #ffffff;
    font-size: 10px;
    font-weight: 700;
    color: #737373;
}

.legal-section h2 {
    margin: 0 0 10px;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.legal-section p {
    max-width: 720px;
    margin: 0 0 10px;
    font-size: 15px;
    line-height: 1.75;
    color: #696969;
}

.legal-section p:last-child {
    margin-bottom: 0;
}

.legal-section ul {
    max-width: 720px;
    margin: 0 0 10px;
    padding-left: 20px;
    color: #696969;
}

.legal-section li {
    margin-bottom: 6px;
    font-size: 15px;
    line-height: 1.7;
}

.legal-section strong {
    color: #111111;
}

.legal-section a {
    color: #111111;
    font-weight: 600;
    text-underline-offset: 3px;
}

.legal-section a:hover {
    text-decoration: underline;
}

.bottom-back {
    margin-top: 40px;
    padding-top: 28px;
    border-top: 1px solid #e5e5e5;
}

.back-button {
    padding: 12px 18px;
    border: 1px solid #d9d9d9;
    border-radius: 9px;
    background: #ffffff;
}

.back-button:hover {
    text-decoration: none;
    border-color: #111111;
}

@media (max-width: 640px) {
    .legal-header {
        padding: 28px 18px 26px;
    }

    .legal-header h1 {
        font-size: 30px;
    }

    .legal-content {
        padding: 28px 18px 56px;
    }

    .legal-section {
        grid-template-columns: 32px 1fr;
        gap: 12px;
        padding: 22px 0;
    }

    .section-number {
        width: 30px;
        height: 30px;
    }

    .legal-section h2 {
        font-size: 16px;
    }

    .legal-section p,
    .legal-section li {
        font-size: 14px;
    }
}
</style>
<template>
    <div class="legal-page">
        <header class="legal-header">
            <div class="header-content">
                <router-link to="/" class="back-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Back to login
                </router-link>

                <span class="eyebrow">CSS PREP</span>
                <h1>Terms of Use</h1>
                <p>
                    These are the rules for using CSS Prep. By using it, you agree to follow them.
                </p>
                <span class="updated">Last updated: {{ lastUpdated }}</span>
            </div>
        </header>

        <main class="legal-content">
            <section class="legal-section">
                <span class="section-number">01</span>
                <div>
                    <h2>What CSS Prep is for</h2>
                    <p>
                        CSS Prep helps you practice for the Computer Systems Servicing NC II competencies. You can
                        take quizzes, identify images, and do practice tasks on a virtual computer.
                    </p>
                    <p>
                        <strong>CSS Prep is not an official TESDA test.</strong> Passing here does not give you a
                        certificate. To get your NC II certificate, you must take the official assessment at a
                        TESDA-accredited center.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">02</span>
                <div>
                    <h2>Your account</h2>
                    <ul>
                        <li>Use your real name and email address.</li>
                        <li>Keep your password to yourself.</li>
                        <li>Do not let anyone else take an assessment using your account.</li>
                        <li>Tell your teacher right away if you think someone used your account.</li>
                        <li>You can delete your account anytime from your Profile page.</li>
                    </ul>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">03</span>
                <div>
                    <h2>Play fair</h2>
                    <p>Please do not:</p>
                    <ul>
                        <li>Look at other learners' answers or results.</li>
                        <li>Try to find answers by looking behind the screen or in the page code.</li>
                        <li>Use bots or automatic tools to take assessments.</li>
                        <li>Try to break the site or change your scores.</li>
                        <li>Post anything rude, harmful, or false.</li>
                    </ul>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">04</span>
                <div>
                    <h2>Taking assessments</h2>
                    <ul>
                        <li><strong>Quizzes and image identification:</strong> answer on your own, unless your teacher
                            says otherwise.</li>
                        <li><strong>Practical tasks:</strong> you have 10 minutes. If the time runs out, the task is not
                            scored.</li>
                        <li><strong>Submitted answers cannot be changed.</strong> To try again, start a new attempt.
                        </li>
                    </ul>
                    <p>
                        If we find someone cheating or using another person's account, we can cancel the results and
                        your teacher can decide what happens next.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">05</span>
                <div>
                    <h2>How scores work</h2>
                    <ul>
                        <li><strong>Quizzes:</strong> one point for each correct answer.</li>
                        <li><strong>Image identification:</strong> one point for each correct image.</li>
                        <li><strong>Practical tasks:</strong> one point for each correct step. Each mistake takes off
                            half a point.</li>
                        <li><strong>Passing mark:</strong> 75 percent, unless the assessment shows a different one.</li>
                    </ul>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">06</span>
                <div>
                    <h2>If a result looks wrong</h2>
                    <p>
                        Tell your teacher and say which assessment and what date you took it. Your teacher can check
                        and fix the result.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">07</span>
                <div>
                    <h2>The site may change</h2>
                    <p>
                        We try to keep CSS Prep working, but it may be slow or down sometimes. Questions and tasks may
                        be updated to match the NC II competencies.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">08</span>
                <div>
                    <h2>Stopping access</h2>
                    <p>
                        An account that breaks these rules may be paused or closed. Your teacher will be told when this
                        happens.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">09</span>
                <div>
                    <h2>Using this in good faith</h2>
                    <p>
                        CSS Prep is a study tool, made as a student project. It is provided as it is. Use it to
                        practice,
                        and do not rely on it as your only preparation for the official assessment.
                    </p>
                </div>
            </section>

            <section class="legal-section">
                <span class="section-number">10</span>
                <div>
                    <h2>Questions</h2>
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
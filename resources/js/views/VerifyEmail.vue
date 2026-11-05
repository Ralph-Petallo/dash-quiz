<template>
    <div class="page">
        <header class="top">
            <router-link to="/" class="brand">
                <img :src="logoUrl" alt="" width="28" height="28" />
                <span>CSS Prep</span>
            </router-link>
        </header>

        <main class="main">
            <div class="stack">
                <section class="panel" aria-labelledby="verification-title">
                    <div class="result" role="status" aria-live="polite">
                        <div class="icon" aria-hidden="true">
                            <svg v-if="isSuccess" class="ui-icon" viewBox="0 0 24 24" fill="none">
                                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            <svg v-else class="ui-icon" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M12 8v5m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>

                        <h1 id="verification-title">
                            {{ isSuccess ? 'Email verified!' : 'Verification link invalid' }}
                        </h1>

                        <p class="lede">
                            {{
                                isSuccess
                                    ? 'Your email has been verified. Sign in to continue to your account.'
                                    : 'This verification link is invalid or has expired. Please try again.'
                            }}
                        </p>

                        <router-link to="/" class="btn-primary">
                            {{ isSuccess ? 'Go to sign in' : 'Back to sign in' }}
                        </router-link>
                    </div>
                </section>
            </div>
        </main>

        <footer class="footer">
            © {{ currentYear }} CSS Prep · SNSU Capstone Project
        </footer>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import logoUrl from '@/assets/lightning.png'

const route = useRoute()
const isSuccess = computed(() => route.query.status === 'success')
const currentYear = new Date().getFullYear()
</script>

<style scoped>
.page {
    --ink: #0a0a0a;
    --mute: #5f5f5f;
    --line: #dcdcdc;
    --paper: #f5f5f4;
    --white: #fff;

    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    background: var(--paper);
    color: var(--ink);
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 15px;
    line-height: 1.5;
    box-sizing: border-box;
}

.page *,
.page *::before,
.page *::after {
    box-sizing: border-box;
}

.page h1,
.page p {
    margin: 0;
}

.page :focus-visible {
    outline: 2px solid var(--ink);
    outline-offset: 2px;
}

/* Header */
.top {
    padding: 18px clamp(16px, 4vw, 40px);
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--ink);
    font-size: 15px;
    font-weight: 800;
    text-decoration: none;
}

.brand img {
    width: 28px;
    height: 28px;
    object-fit: contain;
}

/* Layout */
.main {
    flex: 1;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: clamp(8px, 3vw, 32px) clamp(16px, 4vw, 24px) 48px;
}

.stack {
    width: 100%;
    max-width: 420px;
}

/* Panel */
.panel {
    padding: clamp(24px, 4vw, 34px);
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--white);
}

/* Result */
.result {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.icon {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 50%;
    background: var(--paper);
}

.ui-icon {
    width: 26px;
    height: 26px;
}

.result h1 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.lede {
    color: var(--mute);
    font-size: 14px;
}

.btn-primary {
    width: 100%;
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 4px;
    border: 1px solid var(--ink);
    border-radius: 9px;
    background: var(--ink);
    color: var(--white);
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-primary:hover {
    background: #262626;
}

/* Footer */
.footer {
    padding: 18px 16px;
    border-top: 1px solid var(--line);
    color: var(--mute);
    font-size: 12.5px;
    text-align: center;
}

@media (max-width: 480px) {
    .panel {
        padding: 22px 18px;
        border-radius: 12px;
    }
}

@media (prefers-reduced-motion: reduce) {

    .page *,
    .page *::before,
    .page *::after {
        transition: none !important;
    }
}
</style>
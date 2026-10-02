<template>
    <div class="not-found-wrapper">

        <div class="content">

            <!-- ERROR CODE -->
            <div class="error-code">
                404
            </div>

            <!-- MESSAGE -->
            <div class="text-section">
                <span class="eyebrow">Page Not Found</span>

                <p>
                    The page you're looking for may have moved,
                    been removed, or never existed.
                </p>
            </div>

            <!-- ACTIONS -->
            <div class="actions">

                <button @click="goBack" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Go Back</span>
                </button>

                <router-link to="/" class="btn-home">
                    <i class="fas fa-house"></i>
                    <span>Return Home</span>
                </router-link>

            </div>

        </div>

    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goBack = () => {
    router.back()
}
</script>

<style scoped>
* {
    box-sizing: border-box;
}

/* =========================
   PAGE
========================= */

.not-found-wrapper {
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background: #ffffff;

    color: #111111;

    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    position: relative;
    overflow: hidden;
}

/* =========================
   CONTENT
========================= */

.content {
    width: 100%;
    max-width: 520px;

    display: flex;
    flex-direction: column;
    align-items: center;

    text-align: center;

    position: relative;
    z-index: 2;
}

/* =========================
   ERROR CODE
========================= */

.error-code {
    margin: 0;

    color: #111111;

    font-size: clamp(6rem, 16vw, 9rem);
    line-height: 0.9;

    font-weight: 800;

    letter-spacing: -0.08em;
}

/* =========================
   TEXT
========================= */

.text-section {
    margin-top: 24px;
}

.eyebrow {
    display: block;

    margin-bottom: 8px;

    color: #737373;

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.14em;
}

.text-section h1 {
    margin: 0;

    color: #111111;

    font-size: 24px;
    line-height: 1.25;

    font-weight: 700;

    letter-spacing: -0.02em;
}

.text-section p {
    max-width: 420px;

    margin: 10px auto 0;

    color: #737373;

    font-size: 13px;
    line-height: 1.6;
}

/* =========================
   ACTIONS
========================= */

.actions {
    display: flex;
    align-items: center;
    justify-content: center;

    gap: 8px;

    margin-top: 28px;
}

.btn-back,
.btn-home {
    height: 40px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 0 15px;

    border-radius: 7px;

    font-size: 11px;
    font-weight: 700;

    cursor: pointer;

    text-decoration: none;

    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
}

.btn-back {
    border: 1px solid #e5e5e5;

    background: #ffffff;

    color: #555555;
}

.btn-back:hover {
    border-color: #999999;

    background: #fafafa;

    color: #111111;
}

.btn-home {
    border: 1px solid #111111;

    background: #111111;

    color: #ffffff;
}

.btn-home:hover {
    background: #333333;

    transform: translateY(-1px);
}

.btn-back:active,
.btn-home:active {
    transform: scale(0.98);
}

.btn-back i,
.btn-home i {
    font-size: 10px;
}



/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {


    .logo-placeholder,
    .logo {
        width: 62px;
        height: 62px;
    }

    .error-code {
        font-size: 6rem;
    }

    .text-section {
        margin-top: 20px;
    }

    .text-section h1 {
        font-size: 21px;
    }

    .text-section p {
        font-size: 12px;
    }

    .actions {
        width: 100%;
    }

    .btn-back,
    .btn-home {
        flex: 1;
    }
}

@media (max-width: 380px) {

    .error-code {
        font-size: 5rem;
    }

    .actions {
        flex-direction: column;
        width: 100%;
    }

    .btn-back,
    .btn-home {
        width: 100%;
    }
}
</style>
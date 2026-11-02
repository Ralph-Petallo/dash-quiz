// Color identity for each COC. Shared by the COC catalog and the assessment pages,
// so a COC keeps the same color everywhere.
const THEMES = {
    1: { accent: '#16794F', tint: '#E8F5EE' }, // green
    2: { accent: '#2563EB', tint: '#E8EFFD' }, // blue
    3: { accent: '#8A5A12', tint: '#FBF1E1' }, // amber
    4: { accent: '#6D28D9', tint: '#F1EAFD' }, // violet
}

// Used when a COC has no ready assessments.
const INACTIVE = { accent: '#5F5F5F', tint: '#F1F1F1' } // grey

// Used for a COC number without a color yet.
const FALLBACK = { accent: '#000000', tint: '#F4F4F4' } // black

export const readyCount = (coc) =>
    Number(
        coc?.available_assessment_count ??
        coc?.assessments?.filter((assessment) => assessment.available).length ??
        0
    )

export const isInactiveCoc = (coc) => readyCount(coc) === 0

export function cocTheme(coc) {
    if (isInactiveCoc(coc)) return INACTIVE

    return THEMES[Number(coc?.coc_number)] ?? FALLBACK
}

// Inline CSS variables for a card or page root.
export function cocThemeStyle(coc) {
    const theme = cocTheme(coc)

    return {
        '--coc-accent': theme.accent,
        '--coc-tint': theme.tint,
    }
}
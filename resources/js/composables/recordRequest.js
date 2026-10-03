import api from '../bootstrap'

/*
|--------------------------------------------------------------------------
| Record Page Cache
|--------------------------------------------------------------------------
|
| The list response already contains the detail payload needed by the modal.
| Every record returned by /api/records is cached by ID, so opening the
| modal does not make another request.
|
*/

const recordCache = new Map()
const pageCache = new Map()

const PAGE_CACHE_TTL = 60 * 1000

const cleanParams = (params = {}) => {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => {
            return value !== '' && value !== null && value !== undefined
        })
    )
}

const pageKey = (params = {}) => {
    const cleaned = cleanParams(params)
    const ordered = Object.keys(cleaned)
        .sort()
        .reduce((result, key) => {
            result[key] = cleaned[key]
            return result
        }, {})

    return JSON.stringify(ordered)
}

const normalizeRecord = (record = {}) => ({
    ...record,
    id: Number(record.id),
    user_id: record.user_id != null ? Number(record.user_id) : null,
    quiz_id: record.quiz_id != null ? Number(record.quiz_id) : null,
    coc_number: record.coc_number ?? null,
    score: Number(record.score ?? 0),
    correct_answers: Number(record.correct_answers ?? record.score ?? 0),
    total_questions: Number(record.total_questions ?? 0),
    percentage: Number(record.percentage ?? 0),
    passing_score: Number(record.passing_score ?? 70),
    attempt_count: Number(record.attempt_count ?? 1),
    elapsed_time:
        record.elapsed_time === null || record.elapsed_time === undefined
            ? null
            : Number(record.elapsed_time),
    questions: Array.isArray(record.questions) ? record.questions : [],
})

const rememberRecord = (record) => {
    if (!record?.id) return record

    const normalized = normalizeRecord(record)
    recordCache.set(Number(normalized.id), normalized)

    return normalized
}

const rememberPage = (payload) => {
    const rows = Array.isArray(payload?.data)
        ? payload.data.map(rememberRecord)
        : []

    return {
        ...payload,
        data: rows,
    }
}

const getCachedRecord = (id) => {
    return recordCache.get(Number(id)) ?? null
}

const clearPageCache = () => {
    pageCache.clear()
}

export default () => {
    return {
        /**
         * Load a paginated page of records.
         * Repeated requests for the same page/filter combination use cache.
         */
        async fetch(params = {}, options = {}) {
            const cleaned = cleanParams(params)
            const key = pageKey(cleaned)
            const force = Boolean(options.force)
            const cached = pageCache.get(key)

            if (
                !force &&
                cached &&
                Date.now() - cached.savedAt < PAGE_CACHE_TTL
            ) {
                return cached.payload
            }

            const { data } = await api.get('/api/records', {
                params: cleaned,
            })

            const payload = rememberPage(data)

            pageCache.set(key, {
                savedAt: Date.now(),
                payload,
            })

            return payload
        },

        /**
         * Fallback detail fetch. The Records page itself does not call this
         * when opening the modal because each page row is already cached.
         */
        async fetchById(id, options = {}) {
            const force = Boolean(options.force)

            if (!force) {
                const cached = getCachedRecord(id)
                if (cached) return cached
            }

            const { data } = await api.get(`/api/records/${encodeURIComponent(String(id))}`)
            return rememberRecord(data?.data ?? {})
        },

        /** Return a cached detail row without any server request. */
        getCached(id) {
            return getCachedRecord(id)
        },

        /**
         * Store/refresh one record locally after another assessment flow
         * returns a newly-created QuizRecord. This does NOT create records
         * on the server; assessment submission endpoints remain responsible
         * for saving official records.
         */
        save(record) {
            const saved = rememberRecord(record)
            clearPageCache()
            return saved
        },

        remember(record) {
            return rememberRecord(record)
        },

        clear() {
            recordCache.clear()
            pageCache.clear()
        },

        invalidatePages() {
            clearPageCache()
        },
    }
}

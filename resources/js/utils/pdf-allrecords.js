import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const LOGO_URL = '/lightning.png'

export const EXPORT_ORDERS = {
    newest: 'Date created: newest first',
    oldest: 'Date created: oldest first',
    name: 'Name: A to Z',
}

const INK = [0, 0, 0]
const MUTED = [100, 100, 100]
const RULE = [190, 190, 190]
const GRID = [220, 220, 220]
const HEAD_BG = [30, 30, 30]
const PASS = [35, 110, 65]
const FAIL = [170, 45, 45]

const toTime = (value) => {
    const time = new Date(value).getTime()
    return Number.isFinite(time) ? time : 0
}

const formatDateTime = (value) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'

    return new Intl.DateTimeFormat('en', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

const formatPercent = (value) => {
    if (value === null || value === undefined || value === '') return '—'
    const number = Number(value)
    return Number.isFinite(number) ? `${number.toFixed(1)}%` : '—'
}

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined || seconds === '') return '—'

    const total = Math.max(0, Math.round(Number(seconds) || 0))
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const secs = total % 60
    const pad = (n) => String(n).padStart(2, '0')

    return hours ? `${hours}:${pad(minutes)}:${pad(secs)}` : `${pad(minutes)}:${pad(secs)}`
}

const formatLabel = (value) =>
    value
        ? String(value).replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
        : ''

const resultText = (record) => {
    if (typeof record?.passed === 'boolean') return record.passed ? 'Passed' : 'Not passed'

    const status = String(record?.status ?? '').toLowerCase()
    if (status.includes('pass')) return 'Passed'
    if (status.includes('fail')) return 'Not passed'

    return '—'
}

const scoreText = (record) => {
    const score = record?.score ?? record?.correct_answers
    const total = record?.total_questions

    if (score === null || score === undefined) return '—'
    return total ? `${score} / ${total}` : String(score)
}

/*
 * Turns API records into export rows. Student names and emails come from the
 * users list, with the record's own fields as a fallback.
 */
export function toExportRows(records, usersById) {
    return records.map((record) => {
        const user = usersById.get(Number(record.user_id)) ?? {}
        const firstName = user.first_name ?? record.first_name ?? ''
        const lastName = user.last_name ?? record.last_name ?? ''

        return {
            userId: record.user_id ?? user.id ?? null,
            firstName,
            lastName,
            name: [firstName, lastName].filter(Boolean).join(' ').trim() || `User #${record.user_id ?? '—'}`,
            email: user.email ?? record.email ?? '',
            assessment: record.quiz_title || record.quiz?.title || formatLabel(record.quiz_type) || 'Assessment',
            coc: record.coc_number ? `COC ${record.coc_number}` : '—',
            score: scoreText(record),
            percentage: formatPercent(record.percentage),
            result: resultText(record),
            completedAt: record.completed_at ?? record.created_at ?? null,
            time: formatDuration(record.elapsed_time),
        }
    })
}

/*
 * Date orders use when each attempt was completed.
 * Name order sorts by last name, then first name.
 */
export function sortRows(rows, order) {
    const list = [...rows]

    if (order === 'name') {
        return list.sort(
            (a, b) =>
                a.lastName.localeCompare(b.lastName) ||
                a.firstName.localeCompare(b.firstName) ||
                toTime(a.completedAt) - toTime(b.completedAt)
        )
    }

    const direction = order === 'oldest' ? 1 : -1
    return list.sort((a, b) => direction * (toTime(a.completedAt) - toTime(b.completedAt)))
}

// Stops spreadsheet apps from running a cell that starts with = + - @ as a formula.
const safeCell = (value) => {
    const text = String(value ?? '')
    return /^[=+\-@\t\r]/.test(text) ? `'${text}` : text
}

const csvCell = (value) => {
    const text = safeCell(value)
    return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

export function buildRecordsCsv(rows) {
    const header = ['#', 'Student', 'Email', 'Assessment', 'COC', 'Score', 'Percentage', 'Result', 'Completed', 'Time']

    const body = rows.map((row, index) => [
        index + 1,
        row.name,
        row.email,
        row.assessment,
        row.coc,
        row.score,
        row.percentage,
        row.result,
        formatDateTime(row.completedAt),
        row.time,
    ])

    // The BOM lets Excel read accents and symbols correctly.
    return '\ufeff' + [header, ...body].map((line) => line.map(csvCell).join(',')).join('\r\n')
}

export function downloadBlob(content, filename, type) {
    const blob = new Blob([content], { type })
    const url = URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()

    setTimeout(() => URL.revokeObjectURL(url), 1000)
}

// The logo is optional. The PDF is still created if it is missing or slow to load.
const loadLogo = () =>
    new Promise((resolve) => {
        const image = new Image()
        const timer = setTimeout(() => resolve(null), 2000)

        image.onload = () => {
            clearTimeout(timer)
            resolve(image.naturalWidth > 0 ? image : null)
        }

        image.onerror = () => {
            clearTimeout(timer)
            resolve(null)
        }

        image.src = LOGO_URL
    })

export async function buildAllRecordsPdf(rows, { orderKey = 'newest', passingScore = 75 } = {}) {
    // Landscape gives the table room for all ten columns.
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
    const margin = 12
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()

    const logo = await loadLogo()
    const textX = margin + (logo ? 18 : 0)

    if (logo) {
        try {
            doc.addImage(logo, 'PNG', margin, 10, 14, 14)
        } catch (error) {
            console.warn('Could not add PDF logo:', error)
        }
    }

    // Header
    doc.setTextColor(...INK)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(14)
    doc.text('CSS PREP', textX, 16)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...MUTED)
    doc.text('Competency Assessment and Performance Evaluation System', textX, 21)
    doc.text('All Student Assessment Records', textX, 25)

    doc.text(`Generated ${formatDateTime(new Date())}`, pageWidth - margin, 16, { align: 'right' })
    doc.text(
        `Ordered by ${EXPORT_ORDERS[orderKey] ?? EXPORT_ORDERS.newest}`,
        pageWidth - margin,
        21,
        { align: 'right' }
    )

    doc.setDrawColor(...RULE)
    doc.line(margin, 29, pageWidth - margin, 29)

    // Summary
    const studentCount = new Set(rows.map((row) => row.userId ?? row.name)).size
    const passedCount = rows.filter((row) => row.result === 'Passed').length

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(11)
    doc.setTextColor(...INK)
    doc.text('Assessment Records', margin, 37)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...MUTED)
    doc.text(
        `${rows.length} records  ·  ${studentCount} students  ·  ${passedCount} passed  ·  Passing score ${passingScore}%`,
        margin,
        42
    )

    if (!rows.length) {
        doc.text('No assessment records are available.', margin, 52)
    } else {
        autoTable(doc, {
            startY: 46,
            head: [['#', 'Student', 'Email', 'Assessment', 'COC', 'Score', 'Percent', 'Result', 'Completed', 'Time']],
            body: rows.map((row, index) => [
                index + 1,
                row.name,
                row.email || '—',
                row.assessment,
                row.coc,
                row.score,
                row.percentage,
                row.result,
                formatDateTime(row.completedAt),
                row.time,
            ]),
            theme: 'grid',
            margin: { left: margin, right: margin, bottom: 14 },
            styles: {
                font: 'helvetica',
                fontSize: 7.5,
                cellPadding: 2,
                lineColor: GRID,
                lineWidth: 0.2,
                textColor: [35, 35, 35],
                overflow: 'linebreak',
                valign: 'middle',
            },
            headStyles: {
                fillColor: HEAD_BG,
                textColor: [255, 255, 255],
                fontStyle: 'bold',
            },
            columnStyles: {
                0: { cellWidth: 9, halign: 'center' },
                1: { cellWidth: 38 },
                2: { cellWidth: 52 },
                3: { cellWidth: 'auto' },
                4: { cellWidth: 16 },
                5: { cellWidth: 18, halign: 'center' },
                6: { cellWidth: 17, halign: 'center' },
                7: { cellWidth: 20, halign: 'center' },
                8: { cellWidth: 36 },
                9: { cellWidth: 14, halign: 'center' },
            },
            didParseCell(data) {
                if (data.section === 'body' && data.column.index === 7) {
                    const text = data.cell.raw
                    data.cell.styles.textColor = text === 'Passed' ? PASS : text === 'Not passed' ? FAIL : MUTED
                    data.cell.styles.fontStyle = 'bold'
                }
            },
        })
    }

    // Footer on every page
    const pageCount = doc.internal.getNumberOfPages()

    for (let page = 1; page <= pageCount; page++) {
        doc.setPage(page)

        const footerY = pageHeight - 7

        doc.setDrawColor(...GRID)
        doc.line(margin, footerY - 4, pageWidth - margin, footerY - 4)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7.5)
        doc.setTextColor(...MUTED)
        doc.text('CSS PREP · NC II Readiness', margin, footerY)
        doc.text(`Page ${page} of ${pageCount}`, pageWidth - margin, footerY, { align: 'right' })
    }

    return doc
}
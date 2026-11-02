<template>
  <div class="rj45-page">
    <div class="page-shell">
      <header class="page-header">
        <button type="button" class="back-button" aria-label="Back to assessments" @click="goBack">
          <UiIcon name="arrow-left" />
        </button>
        <div class="page-heading">
          <p class="project-line" :title="assessment.project_title">
            <strong>{{ assessment.app_name }}</strong><span class="slash">/</span>
            <span>{{ assessment.project_subtitle }}</span>
          </p>
          <h1>{{ assessment.title }}</h1>
          <p class="page-description">{{ assessment.description }}</p>
        </div>
        <span class="assessment-badge">
          <UiIcon name="cap" /> Practical assessment
        </span>
      </header>

      <main class="assessment-grid">
        <!-- ASSEMBLY: a real, reactive SVG, not a screenshot of the mockup. -->
        <section class="frost-panel assembly-panel" :aria-labelledby="`${uid}-assembly`">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Cable assembly</p>
              <h2 :id="`${uid}-assembly`">Insert wires into the RJ-45 housing</h2>
            </div>
            <span class="standard-badge">T568{{ selectedStandard }}</span>
          </div>
          <p class="panel-description">Arrange all eight conductors, seat the cable, then crimp the connector.</p>

          <div class="assembly-toolbar">
            <fieldset class="standard-picker" :disabled="testing || completed">
              <legend class="sr-only">Wiring standard</legend>
              <label v-for="standard in ['A', 'B']" :key="standard" :class="{ chosen: selectedStandard === standard }">
                <input type="radio" :name="`${uid}-standard`" :value="standard" :checked="selectedStandard === standard"
                  @change="chooseStandard(standard)" />
                <span>T568{{ standard }}</span>
              </label>
            </fieldset>
            <span class="assembly-state"><span class="state-dot"></span>{{ completed ? 'Submitted' : crimped ?
              'Connector crimped' : inserted ? 'Conductors seated' : `${placedCount}/8 wires placed` }}</span>
          </div>

          <figure class="connector-figure" :class="{ seated: inserted, crimped }">
            <svg ref="connectorSvg" class="connector-svg" viewBox="0 0 560 570" xmlns="http://www.w3.org/2000/svg"
              role="group"
              :aria-label="inserted ? 'Eight conductors seated inside the transparent RJ-45 housing' : 'RJ-45 housing above eight upward-facing conductors'"
              @pointermove="moveBundle" @pointerup="releaseBundle" @pointercancel="cancelBundle">
              <defs>
                <linearGradient :id="`${uid}-glass`" x1="0" x2="1">
                  <stop offset="0" stop-color="#A9A9A9" stop-opacity=".62" />
                  <stop offset=".12" stop-color="#FFFFFF" stop-opacity=".88" />
                  <stop offset=".34" stop-color="#D3D3D3" stop-opacity=".22" />
                  <stop offset=".57" stop-color="#FFFFFF" stop-opacity=".62" />
                  <stop offset=".86" stop-color="#A9A9A9" stop-opacity=".34" />
                  <stop offset="1" stop-color="#FFFFFF" stop-opacity=".86" />
                </linearGradient>
                <linearGradient :id="`${uid}-edge`" x1="0" x2="0" y1="0" y2="1">
                  <stop stop-color="#FFFFFF" />
                  <stop offset=".27" stop-color="#D3D3D3" />
                  <stop offset=".54" stop-color="#FFFFFF" />
                  <stop offset="1" stop-color="#A9A9A9" />
                </linearGradient>
                <linearGradient :id="`${uid}-gold`" x1="0" x2="1">
                  <stop stop-color="#856027" />
                  <stop offset=".26" stop-color="#D8B46D" />
                  <stop offset=".53" stop-color="#FFF2CA" />
                  <stop offset=".72" stop-color="#BB9045" />
                  <stop offset="1" stop-color="#896123" />
                </linearGradient>
                <linearGradient :id="`${uid}-copper`" x1="0" x2="1">
                  <stop stop-color="#9A5828" />
                  <stop offset=".34" stop-color="#F5D2AB" />
                  <stop offset=".63" stop-color="#D98F4C" />
                  <stop offset="1" stop-color="#895023" />
                </linearGradient>
                <linearGradient :id="`${uid}-jacket`" x1="0" x2="1">
                  <stop stop-color="#A9A9A9" />
                  <stop offset=".22" stop-color="#FFFFFF" />
                  <stop offset=".56" stop-color="#D3D3D3" />
                  <stop offset=".82" stop-color="#FFFFFF" />
                  <stop offset="1" stop-color="#A9A9A9" />
                </linearGradient>
                <pattern v-for="wire in stripedWires" :id="`${uid}-wire-${wire.id}`" :key="wire.id" width="14"
                  height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(36)">
                  <rect width="14" height="14" fill="#FFFFFF" />
                  <rect width="5" height="14" :fill="wire.color" />
                </pattern>
                <filter :id="`${uid}-shadow`" x="-30%" y="-20%" width="170%" height="155%">
                  <feDropShadow dx="0" dy="9" stdDeviation="9" flood-color="#000000" flood-opacity=".1" />
                </filter>
              </defs>

              <!-- Contact side faces the learner. The latch is behind the plug. -->
              <ellipse cx="260" cy="535" rx="154" ry="18" fill="#000000" opacity=".035" />
              <path d="M221 203 V291 L232 309 H288 L299 291 V203" :fill="`url(#${uid}-glass)`" stroke="#A9A9A9"
                stroke-width="1.3" opacity=".7" />
              <g :filter="`url(#${uid}-shadow)`">
                <path d="M103 75 Q103 64 114 64 H406 Q417 64 417 75 L426 110 V255 L418 278 H102 L94 255 V110 Z"
                  :fill="`url(#${uid}-glass)`" stroke="#A9A9A9" stroke-width="2" />
                <path d="M119 66 H401 V98 H119 Z" :fill="`url(#${uid}-edge)`" stroke="#A9A9A9" />
                <path d="M119 82 H401 M108 112 H412" stroke="#FFFFFF" stroke-width="3" />
                <path d="M103 105 H417 M101 267 H420" stroke="#696969" stroke-opacity=".35" />
                <rect x="104" y="114" width="312" height="137" rx="7" fill="#FFFFFF" fill-opacity=".15"
                  stroke="#A9A9A9" />
              </g>
              <g v-for="(_, index) in pins" :key="`channel-${index}`">
                <text :x="pinX(index)" y="49" text-anchor="middle" class="svg-number">{{ index + 1 }}</text>
                <path :d="`M${pinX(index) - 15} 66 V98 M${pinX(index) + 15} 66 V98`" stroke="#FFFFFF"
                  stroke-width="1.5" />
                <rect :x="pinX(index) - 14" y="120" width="28" height="148" rx="4" fill="#FFFFFF" fill-opacity=".16"
                  stroke="#A9A9A9" stroke-opacity=".65" />
              </g>

              <!-- The same eight IDs drive the SVG, pin buttons and tester. -->
              <g class="cable-bundle" :class="{ draggable: canInsert }"
                :transform="`translate(${bundleOffset.x} ${bundleOffset.y})`" :role="canInsert ? 'button' : undefined"
                :tabindex="canInsert ? 0 : undefined"
                :aria-label="canInsert ? 'Insert cable into the housing' : undefined" @pointerdown="startBundle"
                @keydown.enter.prevent="insertCable" @keydown.space.prevent="insertCable">
                <rect x="114" :y="inserted ? 277 : 308" width="292" :height="inserted ? 285 : 250" fill="transparent" />
                <g v-for="(id, index) in pins" :key="`wire-${index}`">
                  <path :d="wirePath(index)" fill="none" :stroke="id ? '#696969' : '#D3D3D3'"
                    :stroke-width="id ? 16 : 3" :stroke-dasharray="id ? undefined : '5 7'" stroke-linecap="round" />
                  <path v-if="id" :d="wirePath(index)" fill="none" :stroke="svgWireColor(id)" stroke-width="13.5"
                    stroke-linecap="round" />
                  <path v-if="id" :d="wirePath(index, -3.2)" fill="none" stroke="#FFFFFF" stroke-opacity=".38"
                    stroke-width="2.2" stroke-linecap="round" />
                  <rect v-if="id" :x="pinX(index) - 4.8" :y="inserted ? 166 : 306" width="9.6" height="28" rx="2"
                    :fill="`url(#${uid}-copper)`" stroke="#9A5828" stroke-width=".6" />
                  <path v-if="id" :d="`M${pinX(index) - 2.7} ${inserted ? 169 : 309} v20`" stroke="#FFFFFF"
                    stroke-opacity=".7" />
                </g>
                <template v-if="!inserted">
                  <path
                    d="M122 495 Q121 491 132 492 Q260 509 388 492 Q399 491 398 497 L395 522 Q390 538 361 538 H323 V590 H197 V538 H158 Q129 538 125 522 Z"
                    :fill="`url(#${uid}-jacket)`" stroke="#A9A9A9" stroke-width="1.8" />
                  <path d="M124 495 Q260 513 396 495" fill="none" stroke="#FFFFFF" stroke-width="4" />
                  <path d="M199 512 V588 M321 512 V588" stroke="#A9A9A9" stroke-opacity=".65" />
                  <text x="260" y="557" class="svg-utp" text-anchor="middle">UTP</text>
                </template>
                <template v-else>
                  <rect x="206" y="282" width="108" height="309" rx="15" :fill="`url(#${uid}-jacket)`" stroke="#A9A9A9"
                    stroke-width="1.8" />
                  <path d="M218 310 V570 M302 310 V570" stroke="#A9A9A9" stroke-opacity=".6" />
                  <text x="260" y="447" class="svg-utp" text-anchor="middle">UTP</text>
                </template>
              </g>

              <!-- Contacts and plastic overlays stay in front of inserted wires. -->
              <g v-for="(_, index) in pins" :key="`contact-${index}`">
                <rect :x="pinX(index) - 8" y="134" width="16" height="61" rx="2" :fill="`url(#${uid}-gold)`"
                  stroke="#8C713D" stroke-width=".7" />
                <rect :x="pinX(index) - 9.8" y="127" width="19.6" height="7" rx="2" fill="#FFFFFF" fill-opacity=".8"
                  stroke="#A9A9A9" stroke-width=".5" />
                <rect :x="pinX(index) - 10" y="195" width="20" height="8" rx="2" :fill="`url(#${uid}-edge)`"
                  stroke="#A9A9A9" stroke-width=".5" />
              </g>
              <path
                d="M86 116 Q81 116 81 122 V245 Q81 252 88 252 H98 V116 Z M426 116 H433 Q440 116 440 123 V246 Q440 252 434 252 H426 Z"
                :fill="`url(#${uid}-glass)`" stroke="#A9A9A9" stroke-width="1.5" />
              <path d="M91 126 V242 M431 125 V242" stroke="#FFFFFF" stroke-width="4" />
              <rect x="113" y="215" width="294" height="12" rx="2" :fill="`url(#${uid}-edge)`" fill-opacity=".65"
                stroke="#A9A9A9" />
              <path d="M113 218 H407" stroke="#FFFFFF" stroke-width="2.5" />
              <rect x="98" y="250" width="326" height="29" rx="4" :fill="`url(#${uid}-glass)`" stroke="#696969"
                stroke-opacity=".7" />
              <path d="M103 255 H420 M104 275 H418" stroke="#FFFFFF" stroke-width="3" />
              <rect x="116" y="264" width="288" height="18" rx="4" fill="#FFFFFF" :fill-opacity="inserted ? .08 : .25"
                :stroke="bundleDragging ? '#000000' : '#A9A9A9'" :stroke-width="bundleDragging ? 2.5 : 1" />
              <rect v-if="crimped" x="201" y="272" width="118" height="19" rx="4" fill="#A9A9A9" fill-opacity=".48"
                stroke="#696969" />
              <path d="M111 69 V105 M411 70 V105" stroke="#FFFFFF" stroke-width="3" />

              <!-- Minimal labels, as in the approved mockup. -->
              <g aria-hidden="true">
                <path d="M447 166 h5 q4 0 4 4 v34 q0 4-4 4 h-5" fill="none" stroke="#A9A9A9" />
                <text x="466" y="190" class="svg-note">Housing</text>
                <template v-if="!inserted">
                  <path d="M429 320 h5 q4 0 4 4 v142 q0 4-4 4 h-5" fill="none" stroke="#A9A9A9" />
                  <text x="450" y="394" class="svg-note">Wires</text>
                  <text x="450" y="409" class="svg-note">facing up</text>
                </template>
              </g>
            </svg>
            <figcaption>Contact-side view &middot; Pin 1 to 8, left to right &middot; Latch underneath</figcaption>
          </figure>

          <!-- Keyboard/touch-accessible controls accompany the illustration. -->
          <div class="pin-controls" aria-label="Wire positions; select two filled pins to swap">
            <button v-for="(id, index) in pins" :key="index" type="button" class="pin-button"
              :class="{ selected: Boolean(id && selectedWireId === id), filled: id }" :disabled="!canEdit"
              :data-pin="index + 1" :aria-label="`Pin ${index + 1}: ${id ? wireById[id].name : 'empty'}`"
              :aria-pressed="Boolean(id && selectedWireId === id)" :draggable="canEdit && Boolean(id)"
              @dragstart="startWireDrag(id, $event)" @dragend="endWireDrag" @dragover.prevent
              @drop.prevent.stop="dropOnPin(index, $event)" @click="selectPin(index)"
              @keydown.delete.prevent="removePin(index)" @keydown.backspace.prevent="removePin(index)">
              <span class="position-number">{{ index + 1 }}</span>
              <span v-if="id" class="mini-wire" :style="wireStyle(id)" aria-hidden="true"></span>
              <strong>{{ id ? wireById[id].short : '+' }}</strong>
            </button>
          </div>
          <p class="selection-help" aria-live="polite">{{ selectionHelp }}</p>

          <div v-if="canEdit" class="wire-tools">
            <button type="button" class="text-button" :aria-expanded="trayOpen" @click="trayOpen = !trayOpen">{{
              trayOpen ? 'Hide'
                : 'Show' }} conductors <span>{{ looseWires.length }}</span></button>
            <button v-if="selectedWireId && pins.includes(selectedWireId)" type="button" class="text-button"
              @click="returnSelectedWire">Remove selected</button>
            <button type="button" class="text-button" :disabled="placedCount === 0" @click="clearWiring">Clear
              wires</button>
          </div>
          <div v-if="trayOpen && canEdit" class="wire-tray" @dragover.prevent @drop.prevent="dropToTray">
            <button v-for="wire in looseWires" :key="wire.id" type="button" class="wire-card"
              :class="{ selected: selectedWireId === wire.id }" :aria-pressed="selectedWireId === wire.id"
              :data-wire-id="wire.id" draggable="true" @dragstart="startWireDrag(wire.id, $event)"
              @dragend="endWireDrag" @click="selectWire(wire.id)">
              <span class="wire-swatch" :style="wireStyle(wire.id)" aria-hidden="true"></span>
              <span>{{ wire.name }}</span>
            </button>
            <p v-if="looseWires.length === 0" class="tray-empty">All conductors are placed. Select a wire to remove it,
              or select
              two pins to swap.</p>
          </div>
          <div class="termination-actions">
            <button v-if="!inserted" type="button" class="button termination-button" data-testid="insert"
              :disabled="!canInsert" @click="insertCable">
              <UiIcon name="arrow-up" /> Insert cable
            </button>
            <button v-else-if="!crimped" type="button" class="button termination-button" data-testid="crimp"
              @click="crimpCable">
              <UiIcon name="crimp" /> Crimp connector
            </button>
            <span v-else class="termination-ready">
              <UiIcon name="check" /> Connector seated and crimped
            </span>
            <button v-if="inserted && !completed" type="button" class="text-button" data-testid="edit"
              :disabled="testing" @click="editWiring">Edit wiring</button>
          </div>
        </section>

        <!-- TESTER: real per-position results, never decorative/random LEDs. -->
        <section class="frost-panel tester-panel" :aria-labelledby="`${uid}-tester`">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Cable tester</p>
              <h2 :id="`${uid}-tester`">Check each pin position</h2>
            </div>
            <span class="standard-badge">T568{{ selectedStandard }}</span>
          </div>
          <p class="panel-description">Green means the conductor is in the correct position. Red means it is not.</p>

          <div class="tester-shell" :aria-busy="testing">
            <div class="tester-topline"><span>PIN-POSITION CHECK</span><span>{{ testing ? `Checking ${checkedCount}/8` :
              tested
                ? 'Test complete' : 'Awaiting test' }}</span></div>
            <div class="tester-pins">
              <div v-for="(state, index) in pinStates" :key="index" class="tester-pin" :class="state"
                :data-test-pin="index + 1" :data-state="state">
                <span class="tester-pin-number">PIN {{ index + 1 }}</span>
                <span class="led" aria-hidden="true">
                  <UiIcon :name="state === 'correct' ? 'check' : state === 'wrong' ? 'x' : 'minus'" />
                </span>
                <span class="tester-conductor" :style="wireStyle(pins[index])" aria-hidden="true"></span>
                <strong>{{ pins[index] ? wireById[pins[index]].short : '--' }}</strong>
                <span class="pin-status">{{ state === 'correct' ? 'Correct' : state === 'wrong' ? 'Wrong' : state ===
                  'scanning'
                  ? 'Testing' : 'Untested' }}</span>
                <small v-if="state === 'wrong'">Use {{ expectedWireShort(index) }}</small>
                <small v-else class="expected-spacer" aria-hidden="true">&nbsp;</small>
              </div>
            </div>
            <div class="tester-legend"><span><i class="legend-dot green"></i> Correct position</span><span><i
                  class="legend-dot red"></i> Wrong position</span></div>
          </div>

          <div class="result-banner" :class="tested ? (testPassed ? 'passed' : 'failed') : 'neutral'" role="status"
            aria-live="polite" aria-atomic="true">
            <span class="result-icon" aria-hidden="true">
              <UiIcon :name="tested ? (testPassed ? 'check' : 'alert') : 'info'" />
            </span>
            <div class="result-copy">
              <strong>{{ completed ? 'Practice completed' : tested ? (testPassed ? 'All pin positions are correct' :
                'Pin order needs correction') : testing ? 'Testing pin positions...' : 'Ready when you are' }}</strong>
              <p>{{ resultDescription }}</p>
            </div>
            <span v-if="tested" class="score" data-testid="score">{{ score }}<small>%</small></span>
          </div>

          <div class="action-row">
            <button type="button" class="action-card" data-testid="reset" @click="resetBench">
              <span class="action-icon">
                <UiIcon name="reset" />
              </span><span><strong>Reset</strong><small>Clear and start over</small></span>
            </button>
            <button type="button" class="action-card" data-testid="run-test" :disabled="!canTest" @click="runTest">
              <span class="action-icon">
                <UiIcon name="play" />
              </span><span><strong>{{ testing ? 'Testing...' : tested ? 'Retest' : 'Test' }}</strong><small>Check pin
                  positions</small></span>
            </button>
            <button type="button" class="action-card primary-action" data-testid="complete" :disabled="!canSubmit"
              @click="finishActivity">
              <span class="action-icon">
                <UiIcon name="check" />
              </span><span><strong>{{ completed ? 'Completed' : 'Submit' }}</strong>
                <small>{{ completed ? 'Saved to database' :
                  saving ? 'Saving...' : 'Finish assessment' }}</small>
              </span>
            </button>
          </div>
          <button v-if="completed" type="button" class="button return-button" data-testid="return" @click="goBack">
            <UiIcon name="arrow-left" /> Back to assessments
          </button>
          <p class="simulation-note">{{ assessment.simulation_note }}</p>
        </section>
      </main>
      <footer class="page-footer"><span>{{ assessment.coc_label }}</span><span>Database-backed assessment</span>
      </footer>
      <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </div>

    <Transition name="rj45-modal">
      <div v-if="showResult && submitResult" class="rj45-modal-overlay" @click.self="closeResult">
        <section class="rj45-modal" role="dialog" aria-modal="true" :aria-labelledby="`${uid}-result`">
          <header class="rj45-modal-header">
            <div>
              <p class="eyebrow">Assessment result</p>
              <h2 :id="`${uid}-result`">
                {{ submitResult.passed ? 'Passed' : 'Not yet passed' }}
              </h2>
              <p class="rj45-modal-sub">{{ assessment.title }} · T568{{ submitResult.standard }}</p>
            </div>

            <button type="button" class="icon-close" aria-label="Close result" @click="closeResult">
              <UiIcon name="x" />
            </button>
          </header>

          <div class="rj45-modal-body">
            <div class="result-summary-row">
              <div class="big-score" :class="submitResult.passed ? 'passed' : 'failed'">
                {{ resultPercent }}<small>%</small>
              </div>

              <dl class="stat-grid">
                <div>
                  <dt>Correct</dt>
                  <dd>{{ submitResult.correct_pins }} / 8</dd>
                </div>
                <div>
                  <dt>Attempt</dt>
                  <dd>#{{ submitResult.attempt_count || 1 }}</dd>
                </div>
                <div>
                  <dt>Time</dt>
                  <dd>{{ formatDuration(submitResult.elapsed_time) }}</dd>
                </div>
              </dl>
            </div>

            <h3 class="pin-review-title">Pin by pin</h3>

            <ul class="pin-review">
              <li v-for="pin in resultPins" :key="pin.pin_number" :class="pin.is_correct ? 'ok' : 'bad'">
                <span class="pin-no">{{ pin.pin_number }}</span>
                <span class="pin-led" aria-hidden="true">
                  <UiIcon :name="pin.is_correct ? 'check' : 'x'" />
                </span>
                <span class="pin-detail">
                  <strong>{{ pin.actualShort }}</strong>
                  <small v-if="!pin.is_correct">expected {{ pin.expectedShort }}</small>
                </span>
                <span class="pin-verdict">{{ pin.is_correct ? 'Correct' : 'Wrong' }}</span>
              </li>
            </ul>
          </div>

          <footer class="rj45-modal-footer">
            <button type="button" class="button" @click="closeResult">Review wiring</button>
            <button type="button" class="button termination-button" @click="leaveAfterResult">
              Back to assessments
            </button>
          </footer>
        </section>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, getCurrentInstance, h, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { rj45HandsOnRequest } from '@/assessmentrequests/rj45HandsOn'

const emit = defineEmits(['complete'])
const route = useRoute()
const router = useRouter()
const uid = `css-prep-rj45-${getCurrentInstance()?.uid ?? 0}`

const assessment = ref({
  id: null,
  app_name: '',
  project_title: '',
  project_subtitle: '',
  title: '',
  description: '',
  coc_label: '',
  default_standard: '',
  test_pin_delay_ms: 0,
  tray_order: [],
  initial_wiring: [],
  simulation_note: '',
})
const quiz = ref({})
const wires = ref([])
const standards = ref([])
const loading = ref(true)
const loadError = ref('')
const saving = ref(false)

const connectorSvg = ref(null)
const selectedStandard = ref('')
const pins = ref(Array(8).fill(null))
const selectedWireId = ref(null)
const draggedWireId = ref(null)
const inserted = ref(false)
const crimped = ref(false)
const testing = ref(false)
const tested = ref(false)
const completed = ref(false)
const pinStates = ref(Array(8).fill('pending'))
const testPinResults = ref([])
const trayOpen = ref(true)
const announcement = ref('')
const bundleDragging = ref(false)
const bundleOffset = ref({ x: 0, y: 0 })
const showResult = ref(false)
const submitResult = ref(null)

let bundleStart = null
let activePointerId = null
let testTimer = null
let testToken = 0
let startedAt = null


const quizId = computed(() => route.params.id)
const wireById = computed(() => Object.fromEntries(wires.value.map(wire => [wire.id, wire])))
const stripedWires = computed(() => wires.value.filter(wire => wire.striped))
const supportedStandardCodes = computed(() => standards.value.map(item => item.standard))
const placedCount = computed(() => pins.value.filter(Boolean).length)
const allPlaced = computed(() => pins.value.length === 8 && pins.value.every(id => Boolean(wireById.value[id])) && new Set(pins.value).size === 8)
const canEdit = computed(() => !loading.value && !inserted.value && !testing.value && !completed.value && !saving.value)
const canInsert = computed(() => canEdit.value && allPlaced.value)
const canTest = computed(() => allPlaced.value && inserted.value && crimped.value && !testing.value && !completed.value && !saving.value)
const canSubmit = computed(() =>
  tested.value &&
  crimped.value &&
  !testing.value &&
  !completed.value &&
  !saving.value
)
const looseWires = computed(() => {
  const configured = Array.isArray(assessment.value.tray_order) ? assessment.value.tray_order : []
  const order = [...new Set([...configured, ...wires.value.map(wire => wire.id)])]
  return order.filter(id => wireById.value[id] && !pins.value.includes(id)).map(id => wireById.value[id])
})
const correctCount = computed(() => pinStates.value.filter(state => state === 'correct').length)
const checkedCount = computed(() => pinStates.value.filter(state => state === 'correct' || state === 'wrong').length)
const testPassed = computed(() => tested.value && !testing.value && correctCount.value === 8)
const score = computed(() => correctCount.value * 12.5)
const selectionHelp = computed(() => {
  if (loading.value) return 'Loading assessment data...'
  if (loadError.value) return loadError.value
  if (completed.value) return 'Assessment submitted and saved.'
  if (inserted.value) return 'Wire order is locked. Choose Edit wiring to start a new virtual termination.'
  if (selectedWireId.value) return `${wireById.value[selectedWireId.value]?.name ?? 'Wire'} selected. Choose a pin to place or swap it.`
  return allPlaced.value ? 'Select two filled pins to swap wires. Dragging also works.' : 'Select a conductor below, then choose a numbered pin.'
})
const resultDescription = computed(() => {
  if (completed.value) return `8/8 positions match T568${selectedStandard.value}. The attempt is saved in the database.`
  if (testing.value) return `Checking ${checkedCount.value} of 8 positions against T568${selectedStandard.value}.`
  if (tested.value) return `${correctCount.value}/8 positions match T568${selectedStandard.value}. ${testPassed.value ? 'You can now submit this assessment.' : 'Correct the red positions and test again.'}`
  return crimped.value ? 'The virtual cable is crimped. Test all eight positions.' : 'Arrange the conductors, then insert and crimp the cable.'
})

const resultPercent = computed(() => Number(submitResult.value?.score_percent ?? 0))
const resultPins = computed(() =>
  (submitResult.value?.pins ?? []).map(pin => ({
    ...pin,
    actualShort: wireById.value[pin.actual_wire]?.short ?? '—',
    expectedShort: wireById.value[pin.expected_wire]?.short ?? pin.expected_wire,
  }))
)

function normalizeInitialWiring(values) {
  const used = new Set()
  return Array.from({ length: 8 }, (_, index) => {
    const id = Array.isArray(values) ? values[index] : null
    if (!wireById.value[id] || used.has(id)) return null
    used.add(id)
    return id
  })
}
function defaultStandard() {
  const preferred = assessment.value.default_standard
  if (preferred && supportedStandardCodes.value.includes(preferred)) return preferred
  return supportedStandardCodes.value[0] ?? 'B'
}
async function loadAssessment() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await rj45HandsOnRequest.get(quizId.value)
    const payload = response?.data
    if (!response?.success || !payload) throw new Error('Invalid RJ-45 API response.')

    quiz.value = payload.quiz ?? {}
    assessment.value = payload.assessment ?? {}
    wires.value = Array.isArray(payload.wires) ? payload.wires : []

    standards.value = Array.isArray(payload.standards)
      ? payload.standards.filter(item => item?.standard)
      : []
    selectedStandard.value = defaultStandard()
    pins.value = normalizeInitialWiring(assessment.value.initial_wiring)
    trayOpen.value = pins.value.some(id => !id)
    resetInteractionState(false)
  } catch (error) {
    console.error('Failed to load RJ-45 assessment:', error)
    loadError.value = error?.response?.data?.message || 'Unable to load RJ-45 assessment data.'
  } finally {
    loading.value = false
  }
}
function markStarted() { if (!startedAt) startedAt = new Date() }
function pinX(index) { return 134 + index * 36 }
function wireStyle(id) {
  const wire = wireById.value[id]
  const base = !wire ? '#D3D3D3' : wire.striped ? `repeating-linear-gradient(35deg, #FFFFFF 0 6px, ${wire.color} 6px 10px)` : wire.color
  return { background: `linear-gradient(90deg, rgba(0,0,0,.12), rgba(255,255,255,.35) 30%, transparent 65%, rgba(0,0,0,.12)), ${base}` }
}
function svgWireColor(id) {
  const wire = wireById.value[id]
  return wire?.striped ? `url(#${uid}-wire-${id})` : (wire?.color ?? '#D3D3D3')
}
function expectedWireShort(index) {
  const code = testPinResults.value[index]?.expected_wire
  return wireById.value[code]?.short ?? '—'
}
function wirePath(index, highlight = 0) {
  const x = pinX(index) + highlight
  const startX = (inserted.value ? 226 + index * 9.7 : 141 + index * 34) + highlight
  const startY = inserted.value ? 292 : 508
  const shoulderY = inserted.value ? 240 : 417
  const tipY = inserted.value ? 190 : 331
  return `M${startX} ${startY} C${startX} ${shoulderY + 28}, ${x} ${shoulderY + 26}, ${x} ${shoulderY} L${x} ${tipY}`
}
function chooseStandard(standard) {
  if (testing.value || completed.value || saving.value || !supportedStandardCodes.value.includes(standard) || standard === selectedStandard.value) return
  markStarted()
  invalidateTermination()
  selectedStandard.value = standard
  selectedWireId.value = null
  draggedWireId.value = null
  announcement.value = `T568${standard} selected. Insert and crimp before testing.`
}
function selectWire(id) {
  if (!canEdit.value || !wireById.value[id]) return
  markStarted()
  selectedWireId.value = selectedWireId.value === id ? null : id
}
function selectPin(index) {
  if (!canEdit.value || !Number.isInteger(index) || index < 0 || index > 7) return
  if (selectedWireId.value) placeWire(selectedWireId.value, index)
  else if (pins.value[index]) selectWire(pins.value[index])
  else { trayOpen.value = true; announcement.value = 'Choose a conductor, then this empty pin.' }
}
function placeWire(id, index) {
  if (!canEdit.value || !wireById.value[id] || !Number.isInteger(index) || index < 0 || index > 7) return
  const source = pins.value.indexOf(id)
  if (source === index) { selectedWireId.value = null; draggedWireId.value = null; return }
  markStarted()
  const next = [...pins.value]
  if (source >= 0) next[source] = next[index]
  next[index] = id
  pins.value = next
  selectedWireId.value = null
  draggedWireId.value = null
  invalidateTermination()
  announcement.value = `${wireById.value[id].name} placed in pin ${index + 1}.`
}
function startWireDrag(id, event) {
  if (!canEdit.value || !wireById.value[id]) { event.preventDefault(); return }
  draggedWireId.value = id
  selectedWireId.value = id
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', `css-prep-wire:${id}`)
  }
}
function endWireDrag() { draggedWireId.value = null }
function dropOnPin(index, event) {
  if (event.dataTransfer) event.dataTransfer.dropEffect = 'move'
  if (draggedWireId.value) placeWire(draggedWireId.value, index)
}
function dropToTray() {
  if (!canEdit.value || !draggedWireId.value) return
  selectedWireId.value = draggedWireId.value
  returnSelectedWire()
  draggedWireId.value = null
}
function removePin(index) {
  if (!canEdit.value || !Number.isInteger(index) || index < 0 || index > 7 || !pins.value[index]) return
  markStarted()
  pins.value = pins.value.map((id, position) => position === index ? null : id)
  selectedWireId.value = null
  draggedWireId.value = null
  trayOpen.value = true
  invalidateTermination()
  announcement.value = `Pin ${index + 1} returned to the conductor tray.`
}
function returnSelectedWire() {
  if (!selectedWireId.value) return
  removePin(pins.value.indexOf(selectedWireId.value))
}
function clearWiring() {
  if (!canEdit.value) return
  markStarted()
  invalidateTermination()
  pins.value = Array(8).fill(null)
  selectedWireId.value = null
  draggedWireId.value = null
  trayOpen.value = true
  announcement.value = 'All wires returned to the conductor tray.'
}
function insertCable() {
  if (!canInsert.value) return
  markStarted()
  cancelBundle()
  inserted.value = true
  selectedWireId.value = null
  draggedWireId.value = null
  trayOpen.value = false
  announcement.value = 'Eight conductors seated. Crimp the connector next.'
}
function crimpCable() {
  if (!inserted.value || !allPlaced.value || crimped.value || testing.value || completed.value || saving.value) return
  crimped.value = true
  announcement.value = 'Connector crimped. The Test button is now available.'
}
function editWiring() {
  if (testing.value || completed.value || saving.value) return
  invalidateTermination()
  selectedWireId.value = null
  draggedWireId.value = null
  announcement.value = 'Wire order unlocked. This starts a new virtual termination.'
}
function svgPoint(event) {
  const svg = connectorSvg.value
  const matrix = svg?.getScreenCTM()
  if (!svg || !matrix) return null
  const point = svg.createSVGPoint()
  point.x = event.clientX
  point.y = event.clientY
  return point.matrixTransform(matrix.inverse())
}
function startBundle(event) {
  if (!canInsert.value || event.button > 0) {
    return
  }

  const point = svgPoint(event)
  if (!point) {
    return
  }
  event.preventDefault()
  bundleStart = point
  activePointerId = event.pointerId
  bundleDragging.value = true
  connectorSvg.value?.setPointerCapture(event.pointerId)
}
function moveBundle(event) {
  if (!bundleDragging.value || event.pointerId !== activePointerId || !bundleStart) return
  const point = svgPoint(event)
  if (point) bundleOffset.value = { x: point.x - bundleStart.x, y: point.y - bundleStart.y }
}
function releaseBundle(event) {
  if (!bundleDragging.value || event.pointerId !== activePointerId) return
  const point = svgPoint(event)
  if (point && bundleStart) bundleOffset.value = { x: point.x - bundleStart.x, y: point.y - bundleStart.y }
  const { x, y } = bundleOffset.value
  const tapped = Math.hypot(x, y) < 8
  const aligned = Math.abs(x) < 24 && 306 + y >= 125 && 306 + y <= 282
  cancelBundle()
  if (tapped || aligned) {
    insertCable()
  }
  else announcement.value = 'Move the cable upward so its tips reach the housing opening.'
}
function cancelBundle() {
  const svg = connectorSvg.value
  if (svg && activePointerId !== null && svg.hasPointerCapture?.(activePointerId)) svg.releasePointerCapture(activePointerId)
  activePointerId = null
  bundleStart = null
  bundleDragging.value = false
  bundleOffset.value = { x: 0, y: 0 }
}
function stopTest() {
  testToken += 1
  if (testTimer !== null) {
    clearInterval(testTimer)
    testTimer = null
  }
  testing.value = false
}
function invalidateTermination() {
  stopTest()
  tested.value = false
  pinStates.value = Array(8).fill('pending')
  testPinResults.value = []
  inserted.value = false
  crimped.value = false
  completed.value = false
  cancelBundle()
}
async function runTest() {
  if (!canTest.value) return

  markStarted()
  stopTest()
  testing.value = true
  tested.value = false
  pinStates.value = Array(8).fill('pending')
  announcement.value = 'Checking pin positions with the server.'

  try {
    const response = await rj45HandsOnRequest.test(quizId.value, {
      standard: selectedStandard.value,
      wirings: [...pins.value],
    })

    const result = response?.data
    if (!response?.success || !result || !Array.isArray(result.pins)) {
      throw new Error('Invalid RJ-45 test response.')
    }

    const orderedPins = [...result.pins].sort((a, b) => a.pin_number - b.pin_number)
    testPinResults.value = orderedPins
    const configuredDelay = Number(assessment.value.test_pin_delay_ms)
    const delay = Number.isFinite(configuredDelay)
      ? Math.min(1000, Math.max(0, configuredDelay))
      : 0

    if (delay === 0) {
      pinStates.value = orderedPins.map(pin => pin.is_correct ? 'correct' : 'wrong')
      testing.value = false
      tested.value = true
      announcement.value = `Test complete. ${result.correct_pins} of 8 positions correct.`
      return
    }

    let index = 0
    const token = testToken
    pinStates.value[0] = 'scanning'

    testTimer = setInterval(() => {
      if (token !== testToken || index >= orderedPins.length) return

      pinStates.value[index] = orderedPins[index].is_correct ? 'correct' : 'wrong'
      index += 1

      if (index < orderedPins.length) {
        pinStates.value[index] = 'scanning'
        return
      }

      clearInterval(testTimer)
      testTimer = null
      testing.value = false
      tested.value = true
      announcement.value = `Test complete. ${result.correct_pins} of 8 positions correct.`
    }, delay)
  } catch (error) {
    console.error('Failed to test RJ-45 wiring:', error)
    testing.value = false
    tested.value = false
    pinStates.value = Array(8).fill('pending')
    testPinResults.value = []
    announcement.value = error?.response?.data?.message || 'Unable to test the RJ-45 wiring.'
  }
}
async function finishActivity() {
  if (!canSubmit.value) return

  saving.value = true

  try {
    const response = await rj45HandsOnRequest.submit(quizId.value, {
      standard: selectedStandard.value,
      wirings: [...pins.value],
      crimped: crimped.value,
      tested: tested.value,
      started_at: startedAt?.toISOString() ?? null,
      duration_ms: startedAt ? Date.now() - startedAt.getTime() : null,
    })

    const result = response?.data

    if (!response?.success || !result) {
      throw new Error('Invalid submission response.')
    }

    testPinResults.value = [...(result.pins ?? [])]
      .sort((a, b) => a.pin_number - b.pin_number)

    pinStates.value = testPinResults.value.map(pin =>
      pin.is_correct ? 'correct' : 'wrong'
    )

    tested.value = true
    completed.value = true

    emit('complete', result)

    submitResult.value = result
    showResult.value = true

    announcement.value = result.passed
      ? 'RJ-45 assessment submitted and saved.'
      : `Assessment submitted. ${result.correct_pins} of 8 pins were correct.`
  } catch (error) {
    console.error('Failed to submit RJ-45 assessment:', error)

    announcement.value =
      error?.response?.data?.message ||
      error?.message ||
      'Unable to save the RJ-45 attempt.'
  } finally {
    saving.value = false
  }
}
function resetInteractionState(clearStart = true) {
  stopTest()
  inserted.value = false
  crimped.value = false
  tested.value = false
  completed.value = false
  pinStates.value = Array(8).fill('pending')
  testPinResults.value = []
  selectedWireId.value = null
  draggedWireId.value = null
  cancelBundle()
  if (clearStart) startedAt = null
}
function resetBench() {
  resetInteractionState()
  selectedStandard.value = defaultStandard()
  pins.value = normalizeInitialWiring(assessment.value.initial_wiring)
  trayOpen.value = true
  announcement.value = 'Activity reset.'
}
function goBack() {
  stopTest()
  router.push(`/user/assessments/assessment/${encodeURIComponent(String(quizId.value))}`)
}

function formatDuration(seconds) {
  if (seconds === null || seconds === undefined) return '—'
  const total = Math.max(0, Math.round(Number(seconds)))
  const minutes = Math.floor(total / 60)
  const secs = total % 60
  return minutes > 0 ? `${minutes}m ${secs}s` : `${secs}s`
}
function closeResult() {
  showResult.value = false
}
function leaveAfterResult() {
  showResult.value = false
  goBack()
}
function handleResultEscape(event) {
  if (event.key === 'Escape' && showResult.value) closeResult()
}

watch(() => route.params.id, () => loadAssessment())
onMounted(loadAssessment)
onMounted(() => window.addEventListener('keydown', handleResultEscape))
onUnmounted(() => {
  stopTest()
  cancelBundle()
  window.removeEventListener('keydown', handleResultEscape)
})

const ICON_PATHS = {
  'arrow-left': ['M19 12H5', 'm12 5-7 7 7 7'],
  'arrow-up': ['M12 19V5', 'm5 12 7-7 7 7'],
  check: ['m5 12 4 4L19 6'],
  x: ['m6 6 12 12', 'M18 6 6 18'],
  minus: ['M6 12h12'],
  reset: ['M3 10a9 9 0 1 1 2 8', 'M3 3v7h7'],
  play: ['m8 5 11 7-11 7Z'],
  alert: ['M12 6v7', 'M12 17h.01'],
  info: ['M12 10v7', 'M12 6h.01'],
  crimp: ['M5 3v6l7 5 7-5V3', 'm8 21 4-7 4 7', 'M5 7h14'],
  cap: ['m2 9 10-5 10 5-10 5Z', 'M6 11v6l6 3 6-3v-6', 'M22 9v7'],
}
function UiIcon(props) {
  return h('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.8, 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false' },
    (ICON_PATHS[props.name] || ICON_PATHS.info).map(d => h('path', { d })))
}
UiIcon.props = ['name']
</script>

<style scoped>
/* FROSTED NOIR. Wire colors, metal contacts and test feedback are functional accents. */
.rj45-page {
  --white: #FFFFFF;
  --black: #000000;
  --silver: #A9A9A9;
  --light: #D3D3D3;
  --dim: #696969;
  --green: #11813D;
  --red: #D51C2B;
  min-height: 100vh;
  width: 100%;
  color: var(--black);
  background: radial-gradient(ellipse at 4% 4%, rgba(211, 211, 211, .25), transparent 45%),
    radial-gradient(ellipse at 95% 80%, rgba(211, 211, 211, .2), transparent 50%), var(--white);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color-scheme: light;
}

.rj45-page *,
.rj45-page *::before,
.rj45-page *::after {
  box-sizing: border-box;
}

button,
input {
  font: inherit;
}

button {
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  color: inherit;
}

button:disabled {
  cursor: not-allowed;
}

button:focus-visible,
input:focus-visible,
[role="button"]:focus-visible {
  outline: 3px solid var(--black);
  outline-offset: 3px;
}

h1,
h2,
h3,
p,
figure,
fieldset {
  margin: 0;
}

.page-shell {
  width: min(1440px, 100%);
  padding: 30px 34px 22px;
  margin: auto;
}

.page-header {
  display: flex;
  gap: 22px;
  align-items: center;
  margin-bottom: 34px;
}

.back-button {
  flex: 0 0 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border: 1px solid var(--light);
  border-radius: 13px;
  background: var(--white);
  box-shadow: 0 2px 5px rgba(0, 0, 0, .035);
}

.back-button:hover {
  background: rgba(211, 211, 211, .2);
}

.page-heading {
  flex: 1;
  min-width: 0;
}

.project-line {
  display: flex;
  align-items: baseline;
  gap: 13px;
  color: var(--dim);
  font-size: 11px;
  margin-bottom: 7px;
}

.project-line strong {
  flex-shrink: 0;
  text-transform: uppercase;
  letter-spacing: .035em;
}

.project-line .slash {
  color: var(--silver);
}

h1 {
  font-size: clamp(25px, 2.6vw, 33px);
  font-weight: 750;
  letter-spacing: -.045em;
  line-height: 1.2;
}

.page-description {
  margin-top: 5px;
  color: var(--dim);
  font-size: 14px;
}

.assessment-badge {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: rgba(255, 255, 255, .75);
  color: var(--dim);
  font-size: 11px;
}

.assessment-badge svg {
  width: 16px;
  height: 16px;
}

.assessment-grid {
  display: grid;
  grid-template-columns: minmax(0, .98fr) minmax(0, 1.05fr);
  gap: 16px;
  align-items: stretch;
}

.frost-panel {
  min-width: 0;
  padding: 25px;
  border: 1px solid rgba(169, 169, 169, .37);
  border-radius: 20px;
  background: rgba(255, 255, 255, .88);
  box-shadow: 0 17px 45px rgba(0, 0, 0, .05), inset 0 1px 0 var(--white);
}

.panel-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
}

.panel-heading>div {
  min-width: 0;
}

.eyebrow {
  text-transform: uppercase;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .13em;
  color: var(--dim);
  margin-bottom: 9px;
}

h2 {
  font-size: clamp(20px, 1.65vw, 24px);
  font-weight: 750;
  letter-spacing: -.035em;
  line-height: 1.3;
}

.standard-badge {
  white-space: nowrap;
  padding: 6px 10px;
  border: 1px solid var(--light);
  border-radius: 9px;
  background: rgba(255, 255, 255, .8);
  font-size: 12px;
  font-weight: 750;
}

.panel-description {
  margin-top: 10px;
  color: var(--dim);
  font-size: 13px;
  line-height: 1.6;
}

.assembly-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 18px 0 4px;
}

.standard-picker {
  border: 0;
  padding: 3px;
  display: inline-flex;
  gap: 3px;
  border-radius: 9px;
  background: rgba(211, 211, 211, .26);
}

.standard-picker label {
  position: relative;
  display: block;
  cursor: pointer;
}

.standard-picker input {
  position: absolute;
  width: 100%;
  height: 100%;
  inset: 0;
  opacity: 0;
  margin: 0;
  cursor: inherit;
}

.standard-picker label>span {
  display: block;
  font-size: 10px;
  padding: 4px 9px;
  border: 1px solid transparent;
  border-radius: 6px;
  color: var(--dim);
}

.standard-picker .chosen>span {
  color: var(--black);
  background: var(--white);
  border-color: var(--light);
  font-weight: 700;
  box-shadow: 0 1px 3px rgba(0, 0, 0, .025);
}

.standard-picker input:focus-visible+span {
  outline: 2px solid var(--black);
  outline-offset: 2px;
}

.standard-picker:disabled {
  opacity: .55;
}

.assembly-state {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--dim);
  font-size: 10px;
}

.state-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--dim);
}

.connector-figure {
  min-width: 0;
}

.connector-svg {
  display: block;
  width: 100%;
  max-height: 483px;
  user-select: none;
}

.cable-bundle.draggable {
  cursor: grab;
  touch-action: none;
}

.cable-bundle.draggable:active {
  cursor: grabbing;
}

.svg-number {
  fill: var(--black);
  font-size: 13px;
  font-weight: 700;
}

.svg-note {
  fill: var(--dim);
  font-size: 11px;
}

.svg-utp {
  fill: var(--dim);
  font-size: 18px;
  font-weight: 650;
  letter-spacing: 2.5px;
}

figcaption {
  font-size: 10px;
  text-align: center;
  color: var(--dim);
  margin: 4px 0 15px;
}

.pin-controls {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 5px;
}

.pin-button {
  min-width: 0;
  min-height: 45px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  position: relative;
  padding: 6px 2px;
  border: 1px dashed var(--silver);
  border-radius: 8px;
  background: var(--white);
}

.pin-button.filled {
  border-style: solid;
  border-color: var(--light);
  cursor: grab;
}

.pin-button:hover:not(:disabled),
.pin-button.selected {
  border-color: var(--black);
  background: rgba(211, 211, 211, .14);
}

.pin-button.selected {
  box-shadow: inset 0 0 0 1px var(--black);
}

.pin-button:disabled {
  cursor: default;
}

.pin-button .position-number {
  color: var(--dim);
  font-size: 9px;
}

.pin-button strong {
  font-size: 10px;
  font-weight: 650;
}

.mini-wire {
  position: absolute;
  right: 4px;
  top: 7px;
  width: 4px;
  height: 12px;
  border-radius: 3px;
}

.selection-help {
  min-height: 30px;
  color: var(--dim);
  font-size: 11px;
  margin-top: 10px;
}

.wire-tools {
  display: flex;
  gap: 14px;
  align-items: center;
  flex-wrap: wrap;
}

.text-button {
  min-height: 34px;
  border: 0;
  padding: 5px 0;
  font-size: 11px;
  font-weight: 600;
  background: transparent;
  color: var(--dim);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.text-button:hover:not(:disabled) {
  color: var(--black);
}

.text-button:disabled {
  opacity: .4;
}

.text-button>span {
  margin-left: 4px;
  display: inline-block;
  min-width: 16px;
  text-align: center;
  border: 1px solid var(--light);
  border-radius: 5px;
  font-size: 9px;
  text-decoration: none;
}

.wire-tray {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 6px;
  padding: 10px;
  margin: 6px 0 12px;
  border: 1px dashed var(--light);
  border-radius: 11px;
  background: rgba(211, 211, 211, .1);
}

.wire-card {
  min-height: 47px;
  padding: 7px;
  display: flex;
  align-items: center;
  gap: 6px;
  text-align: left;
  border: 1px solid var(--light);
  border-radius: 8px;
  background: var(--white);
  font-size: 10px;
  cursor: grab;
}

.wire-card:hover,
.wire-card.selected {
  border-color: var(--black);
}

.wire-card.selected {
  box-shadow: inset 0 0 0 1px var(--black);
}

.wire-swatch {
  flex: 0 0 8px;
  height: 28px;
  border: 1px solid rgba(0, 0, 0, .2);
  border-radius: 5px;
}

.tray-empty {
  grid-column: 1/-1;
  color: var(--dim);
  font-size: 11px;
  line-height: 1.6;
}

.termination-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-top: 1px solid rgba(211, 211, 211, .55);
  margin-top: 9px;
  padding-top: 13px;
}

.button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 10px 16px;
  background: var(--white);
  border: 1px solid var(--light);
  border-radius: 9px;
  font-weight: 650;
  font-size: 12px;
}

.button:hover:not(:disabled) {
  border-color: var(--black);
}

.button:disabled {
  opacity: .4;
}

.termination-button {
  background: var(--black);
  color: var(--white);
  border-color: var(--black);
}

.termination-button:hover:not(:disabled) {
  background: var(--dim);
}

.termination-ready {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--dim);
}

.termination-ready svg {
  width: 16px;
  height: 16px;
}

.tester-panel {
  display: flex;
  flex-direction: column;
}

.tester-shell {
  margin-top: 30px;
  padding: 20px 13px 23px;
  border: 1px solid rgba(169, 169, 169, .34);
  border-radius: 16px;
  background: linear-gradient(145deg, rgba(255, 255, 255, .6), rgba(211, 211, 211, .065));
}

.tester-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 0 2px;
  margin-bottom: 18px;
  font-size: 9px;
  color: var(--dim);
  letter-spacing: .04em;
}

.tester-pins {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 6px;
}

.tester-pin {
  min-width: 0;
  min-height: 213px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 14px 2px 10px;
  border: 1px solid var(--light);
  border-radius: 8px;
  background: rgba(255, 255, 255, .8);
}

.tester-pin-number {
  white-space: nowrap;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .055em;
  color: var(--dim);
}

.led {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--light);
  color: var(--dim);
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, .6), 0 2px 3px rgba(0, 0, 0, .035);
}

.led svg {
  width: 23px;
  height: 23px;
  stroke-width: 3;
}

.tester-conductor {
  width: 11px;
  height: 43px;
  flex-shrink: 0;
  border: 1px solid rgba(0, 0, 0, .18);
  border-radius: 7px;
}

.tester-pin>strong {
  font-size: 11px;
  font-weight: 750;
}

.pin-status {
  font-size: 10px;
  font-weight: 700;
  color: var(--dim);
}

.tester-pin small {
  font-size: 8px;
  font-weight: 600;
  color: var(--red);
  line-height: 1;
  white-space: nowrap;
}

.tester-pin.scanning {
  border-color: var(--black);
}

.tester-pin.scanning .led {
  background: var(--dim);
  color: var(--white);
}

.tester-pin.correct {
  border-color: rgba(17, 129, 61, .5);
  background: rgba(17, 129, 61, .045);
}

.tester-pin.wrong {
  border-color: var(--red);
  background: rgba(213, 28, 43, .035);
}

.correct .led {
  color: var(--white);
  background: var(--green);
}

.wrong .led {
  color: var(--white);
  background: var(--red);
}

.correct .pin-status {
  color: var(--green);
}

.wrong .pin-status {
  color: var(--red);
}

.tester-legend {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 23px;
  margin-top: 28px;
  color: var(--dim);
  font-size: 11px;
}

.tester-legend>span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.legend-dot {
  width: 8px;
  height: 8px;
  display: inline-block;
  border-radius: 50%;
}

.legend-dot.green {
  background: var(--green);
}

.legend-dot.red {
  background: var(--red);
}



.result-banner {
  min-height: 99px;
  display: flex;
  align-items: center;
  gap: 13px;
  margin-top: 22px;
  padding: 18px 17px;
  border: 1px solid var(--light);
  border-radius: 12px;
  background: rgba(211, 211, 211, .065);
}

.result-icon {
  flex: 0 0 29px;
  width: 29px;
  height: 29px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--light);
  color: var(--dim);
}

.result-copy {
  flex: 1;
  min-width: 0;
}

.result-copy>strong {
  font-size: 13px;
  font-weight: 700;
}

.result-copy>p {
  color: var(--dim);
  font-size: 11px;
  line-height: 1.55;
  margin-top: 3px;
}

.result-banner.failed {
  border-color: var(--red);
  background: rgba(213, 28, 43, .035);
}

.result-banner.passed {
  border-color: var(--green);
  background: rgba(17, 129, 61, .035);
}

.result-banner.failed .result-icon {
  background: var(--red);
  color: var(--white);
}

.result-banner.passed .result-icon {
  background: var(--green);
  color: var(--white);
}

.failed .result-copy>strong {
  color: var(--red);
}

.passed .result-copy>strong {
  color: var(--green);
}

.score {
  font-size: clamp(26px, 2.6vw, 35px);
  font-weight: 750;
  letter-spacing: -.05em;
  white-space: nowrap;
  line-height: 1;
}

.score small {
  font-size: 17px;
  margin-left: 2px;
}

.action-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1.1fr;
  gap: 10px;
  margin-top: 28px;
}

.action-card {
  min-width: 0;
  min-height: 76px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 9px;
  text-align: left;
  border: 1px solid var(--light);
  border-radius: 12px;
  background: linear-gradient(140deg, var(--white), rgba(211, 211, 211, .1));
  box-shadow: 0 2px 4px rgba(0, 0, 0, .025);
}

.action-icon {
  width: 33px;
  height: 35px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid rgba(169, 169, 169, .3);
  border-radius: 9px;
  background: rgba(255, 255, 255, .75);
}

.action-card strong {
  display: block;
  font-size: 12px;
}

.action-card small {
  display: block;
  font-size: 9px;
  color: var(--dim);
  margin-top: 4px;
}

.action-card:hover:not(:disabled) {
  border-color: var(--black);
}

.action-card:disabled {
  opacity: .45;
}

.primary-action {
  background: linear-gradient(155deg, var(--dim), var(--black) 80%);
  border-color: var(--black);
  color: var(--white);
}

.primary-action .action-icon {
  color: var(--white);
  background: rgba(255, 255, 255, .08);
  border-color: transparent;
}

.primary-action small {
  color: var(--light);
}

.primary-action:disabled {
  opacity: .6;
}

.return-button {
  align-self: flex-start;
  margin-top: 16px;
}

.simulation-note {
  margin-top: auto;
  padding-top: 34px;
  color: var(--dim);
  font-size: 10px;
  line-height: 1.7;
  max-width: 540px;
}

.page-footer {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 17px 3px 0;
  color: var(--dim);
  font-size: 10px;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  padding: 0;
  border: 0;
}

/* Result modal */

.rj45-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(0, 0, 0, .35);
  backdrop-filter: blur(6px);
}

.rj45-modal {
  width: min(560px, 100%);
  max-height: min(90vh, 760px);
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(169, 169, 169, .45);
  border-radius: 20px;
  background: var(--white);
  box-shadow: 0 30px 80px rgba(0, 0, 0, .22);
  overflow: hidden;
}

.rj45-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  padding: 22px 24px 18px;
  border-bottom: 1px solid rgba(211, 211, 211, .6);
}

.rj45-modal-header h2 {
  font-size: 22px;
}

.rj45-modal-sub {
  margin-top: 5px;
  color: var(--dim);
  font-size: 12px;
}

.icon-close {
  flex: 0 0 36px;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: var(--white);
  color: var(--dim);
}

.icon-close:hover {
  border-color: var(--black);
  color: var(--black);
}

.rj45-modal-body {
  padding: 20px 24px;
  overflow-y: auto;
}

.result-summary-row {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 18px;
  border: 1px solid var(--light);
  border-radius: 14px;
  background: rgba(211, 211, 211, .08);
}

.big-score {
  flex: 0 0 auto;
  font-size: 48px;
  font-weight: 750;
  letter-spacing: -.05em;
  line-height: 1;
}

.big-score small {
  font-size: 20px;
  margin-left: 2px;
}

.big-score.passed {
  color: var(--green);
}

.big-score.failed {
  color: var(--red);
}

.stat-grid {
  flex: 1;
  min-width: 0;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin: 0;
}

.stat-grid>div {
  min-width: 0;
}

.stat-grid dt {
  font-size: 9px;
  font-weight: 750;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--dim);
}

.stat-grid dd {
  margin: 4px 0 0;
  font-size: 14px;
  font-weight: 700;
  white-space: nowrap;
}

.pin-review-title {
  margin: 22px 0 10px;
  font-size: 11px;
  font-weight: 750;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--dim);
}

.pin-review {
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid var(--light);
  border-radius: 12px;
  overflow: hidden;
}

.pin-review li {
  display: grid;
  grid-template-columns: 28px 26px minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(211, 211, 211, .6);
  font-size: 12px;
}

.pin-review li:last-child {
  border-bottom: 0;
}

.pin-no {
  color: var(--dim);
  font-size: 11px;
  font-weight: 700;
}

.pin-led {
  width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--white);
}

.pin-led svg {
  width: 14px;
  height: 14px;
  stroke-width: 3;
}

.pin-review li.ok .pin-led {
  background: var(--green);
}

.pin-review li.bad .pin-led {
  background: var(--red);
}

.pin-detail {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.pin-detail small {
  color: var(--red);
  font-size: 10px;
}

.pin-verdict {
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.pin-review li.ok .pin-verdict {
  color: var(--green);
}

.pin-review li.bad .pin-verdict {
  color: var(--red);
}

.rj45-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid rgba(211, 211, 211, .6);
  background: var(--white);
}

.rj45-modal-footer .button {
  min-height: 40px;
}

.rj45-modal-enter-active,
.rj45-modal-leave-active {
  transition: opacity .16s ease;
}

.rj45-modal-enter-active .rj45-modal,
.rj45-modal-leave-active .rj45-modal {
  transition: transform .16s ease, opacity .16s ease;
}

.rj45-modal-enter-from,
.rj45-modal-leave-to {
  opacity: 0;
}

.rj45-modal-enter-from .rj45-modal,
.rj45-modal-leave-to .rj45-modal {
  opacity: 0;
  transform: translateY(8px) scale(.985);
}

/* Responsive */

@media (max-width: 1200px) and (min-width: 981px) {
  .page-shell {
    padding: 24px;
  }

  .frost-panel {
    padding: 21px;
  }

  .tester-pins {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .tester-pin {
    min-height: 166px;
    gap: 5px;
    padding-top: 10px;
  }

  .tester-conductor {
    height: 29px;
  }

  .action-card {
    flex-direction: column;
    text-align: center;
    gap: 7px;
    padding: 10px 5px;
  }

  .project-line {
    font-size: 10px;
    gap: 9px;
  }

  .wire-tray {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 980px) {
  .assessment-grid {
    grid-template-columns: 1fr;
  }

  .page-shell {
    max-width: 780px;
    padding: 24px;
  }

  .assessment-badge {
    display: none;
  }

  .project-line {
    font-size: 10px;
  }

  .connector-svg {
    max-height: 455px;
  }

  .tester-panel {
    min-height: auto;
  }

  .simulation-note {
    padding-top: 24px;
  }
}

@media (max-width: 600px) {
  .page-shell {
    padding: 20px 12px 18px;
  }

  .page-header {
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 22px;
  }

  .back-button {
    flex-basis: 38px;
    height: 39px;
    border-radius: 10px;
    margin-top: 2px;
  }

  .project-line {
    flex-wrap: wrap;
    gap: 4px 8px;
    font-size: 9px;
    margin-bottom: 7px;
    line-height: 1.45;
  }

  .project-line>span:last-child {
    flex-basis: 100%;
  }

  .project-line .slash {
    display: none;
  }

  h1 {
    font-size: 24px;
  }

  .page-description {
    font-size: 12px;
    margin-top: 7px;
  }

  .frost-panel {
    padding: 20px 15px;
    border-radius: 17px;
  }

  .panel-heading {
    gap: 10px;
  }

  h2 {
    font-size: 20px;
  }

  .eyebrow {
    font-size: 9px;
    margin-bottom: 7px;
  }

  .standard-badge {
    font-size: 10px;
    padding: 5px 7px;
    border-radius: 7px;
  }

  .panel-description {
    font-size: 12px;
  }

  .assembly-toolbar {
    margin-top: 15px;
    gap: 8px;
  }

  .assembly-state {
    font-size: 9px;
  }

  .standard-picker label>span {
    font-size: 9px;
    padding: 4px 8px;
  }

  .connector-svg {
    max-height: none;
  }

  figcaption {
    font-size: 9px;
    margin-bottom: 13px;
  }

  .pin-controls {
    gap: 4px;
  }

  .pin-button {
    min-height: 48px;
  }

  .pin-button strong {
    font-size: 9px;
  }

  .mini-wire {
    display: none;
  }

  .selection-help {
    min-height: 0;
    font-size: 10px;
    margin-top: 10px;
  }

  .wire-tools {
    gap: 12px;
    margin-top: 3px;
  }

  .text-button {
    min-height: 42px;
  }

  .wire-tray {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .wire-card {
    font-size: 11px;
    min-height: 47px;
  }

  .termination-actions {
    flex-wrap: wrap;
  }

  .termination-ready {
    font-size: 10px;
  }

  .tester-shell {
    padding: 15px 10px 19px;
    margin-top: 22px;
    border-radius: 12px;
  }

  .tester-topline {
    font-size: 8px;
    margin-bottom: 14px;
  }

  .tester-pins {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 7px;
  }

  .tester-pin {
    min-height: 173px;
    gap: 6px;
    padding: 11px 3px 8px;
  }

  .tester-conductor {
    height: 31px;
  }

  .tester-pin small {
    font-size: 9px;
  }

  .tester-legend {
    font-size: 10px;
    gap: 15px;
    margin-top: 19px;
  }

  .result-banner {
    margin-top: 18px;
    gap: 9px;
    padding: 15px 11px;
  }

  .result-icon {
    flex-basis: 24px;
    width: 24px;
    height: 24px;
  }

  .result-icon svg {
    width: 17px;
    height: 17px;
  }

  .result-copy>strong {
    font-size: 12px;
  }

  .result-copy>p {
    font-size: 10px;
  }

  .score {
    font-size: 27px;
  }

  .score small {
    font-size: 13px;
  }

  .action-row {
    gap: 7px;
    margin-top: 20px;
  }

  .action-card {
    min-height: 92px;
    flex-direction: column;
    text-align: center;
    gap: 7px;
    padding: 10px 5px;
  }

  .action-icon {
    width: 29px;
    height: 29px;
  }

  .action-icon svg {
    width: 17px;
    height: 17px;
  }

  .action-card strong {
    font-size: 11px;
  }

  .action-card small {
    font-size: 8px;
  }

  .page-footer {
    font-size: 9px;
    gap: 15px;
  }

  .page-footer>span:last-child {
    max-width: 170px;
    text-align: right;
  }

  .rj45-modal-overlay {
    padding: 10px;
  }

  .result-summary-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 14px;
  }

  .stat-grid {
    width: 100%;
  }

  .rj45-modal-footer {
    flex-direction: column-reverse;
  }

  .rj45-modal-footer .button {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    transition: none !important;
    animation: none !important;
  }
}
</style>
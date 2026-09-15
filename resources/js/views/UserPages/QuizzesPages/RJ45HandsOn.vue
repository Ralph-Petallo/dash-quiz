<template>
  <div class="rj45-page">

    <!-- HEADER -->
    <header class="assessment-header">
      <div class="header-left">
        <button class="back-btn" type="button" title="Go back" @click="router.back()">
          <i class="fas fa-arrow-left"></i>
        </button>

        <div>
          <span class="assessment-label">COC 1 / Practical Bench</span>
          <h1>RJ-45 Cable Termination</h1>
        </div>
      </div>

      <div class="progress-info">
        <div class="progress-top">
          <span>Stage {{ stage }} of 4</span>
          <span>{{ progress }}%</span>
        </div>

        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
        </div>
      </div>
    </header>

    <!-- MAIN -->
    <main class="assessment-container">

      <!-- SCENARIO -->
      <section class="scenario-card">
        <div class="scenario-top">
          <span class="scenario-kicker">
            <i class="fas fa-network-wired"></i>
            COC 1 / Practical Task
          </span>

          <span class="ticket-badge">
            NET-045
          </span>
        </div>

        <h2>
          Terminate and verify a replacement Ethernet patch cable.
        </h2>

        <p>
          Complete the cable preparation, arrange the conductors,
          insert them into the RJ-45 plug, crimp the connector,
          and verify continuity.
        </p>
      </section>

      <!-- WORK AREA -->
      <section class="bench-layout">

        <!-- PROCEDURE -->
        <aside class="procedure-panel">
          <div class="panel-heading">
            <div>
              <span class="eyebrow">Procedure</span>
              <h3>Termination steps</h3>
            </div>

            <span class="bench-code">RJ45</span>
          </div>

          <button v-for="stepItem in procedureSteps" :key="stepItem.id" type="button" class="procedure-step" :class="{
            current: stage === stepItem.id,
            complete: stage > stepItem.id
          }" :disabled="stage < stepItem.id" @click="goToCompletedStage(stepItem.id)">
            <span class="step-icon">
              <i v-if="stage > stepItem.id" class="fas fa-check"></i>

              <span v-else>
                {{ stepItem.id }}
              </span>
            </span>

            <span class="step-text">
              <strong>{{ stepItem.title }}</strong>
              <small>{{ stepItem.detail }}</small>
            </span>

            <i v-if="stage > stepItem.id" class="fas fa-check"></i>

            <i v-else-if="stage === stepItem.id" class="fas fa-circle"></i>
          </button>

          <div class="standard-note">
            <i class="fas fa-circle-info"></i>

            <span>
              Default wiring standard:
              <strong>T568B</strong>
            </span>
          </div>
        </aside>

        <!-- WORK SURFACE -->
        <section class="work-surface">

          <!-- SURFACE HEADER -->
          <div class="surface-header">
            <div>
              <span class="eyebrow">
                Stage {{ stage }}
              </span>

              <h3>
                {{ currentStep.title }}
              </h3>
            </div>

            <div class="stage-badge">
              {{ stage }}/4
            </div>
          </div>

          <!-- ================= STAGE 1 ================= -->
          <div v-if="stage === 1" class="stage-content">

            <div class="instruction">
              <div class="instruction-title">
                <span class="instruction-number">1</span>
                <div>
                  <strong>Choose wiring standard</strong>
                  <span>
                    Select which pinout you will use for this cable.
                  </span>
                </div>
              </div>
            </div>

            <div class="standard-options">
              <button v-for="standard in ['A', 'B']" :key="standard" type="button" class="standard-card"
                :class="{ selected: selectedStandard === standard }" @click="chooseStandard(standard)">
                <div class="standard-card-top">
                  <strong>T568{{ standard }}</strong>

                  <i v-if="selectedStandard === standard" class="fas fa-circle-check"></i>
                </div>

                <span>
                  {{
                    standard === 'A'
                      ? 'White-Green starts at Pin 1'
                      : 'White-Orange starts at Pin 1'
                  }}
                </span>
              </button>
            </div>

            <div class="instruction">
              <div class="instruction-title">
                <span class="instruction-number">2</span>
                <div>
                  <strong>Cut and strip the cable</strong>
                  <span>
                    Drag the scissors onto the marked area,
                    or use the button.
                  </span>
                </div>
              </div>
            </div>

            <div class="cable-work-area" @dragover.prevent @drop="dropScissors">

              <div class="cut-target" :class="{ active: scissorDragged }">
                <span class="cut-line"></span>

                <span class="cut-target-label">
                  CUT HERE
                </span>

                <div class="cable-body" :class="{ stripped: cableCut }">
                  <span v-for="n in 3" :key="n" class="cable-ring"></span>
                </div>

                <div class="exposed-wires-preview" :class="{ visible: cableCut }">
                  <span v-for="wire in targetOrder" :key="wire.id" class="preview-wire" :class="wire.className"></span>
                </div>
              </div>

              <button type="button" class="tool-card" draggable="true" @dragstart="dragScissors"
                @dragend="scissorDragged = false" @click="cutCable">
                <i class="fas fa-scissors"></i>

                <span>
                  <strong>Scissors</strong>
                  <small>
                    Drag to cable or tap
                  </small>
                </span>
              </button>
            </div>

            <div class="action-row">
              <button type="button" class="action-btn primary" :disabled="cableCut" @click="cutCable">
                <i class="fas fa-scissors"></i>

                {{
                  cableCut
                    ? "Cable prepared"
                    : "Cut and strip cable"
                }}
              </button>
            </div>
          </div>

          <!-- ================= STAGE 2 ================= -->
          <div v-else-if="stage === 2" class="stage-content wiring-stage">

            <div class="task-banner">
              <div class="task-banner-icon">
                <i class="fas fa-grip-lines"></i>
              </div>

              <div>
                <strong>Arrange the 8 conductors</strong>
                <span>
                  Drag a wire into a pin, or tap a wire and then tap
                  a pin.
                </span>
              </div>
            </div>

            <!-- WIRE TRAY -->
            <div class="wire-section">
              <div class="section-label">
                Available conductors
                <span>{{ looseWires.length }}/8</span>
              </div>

              <div class="wire-tray" @dragover.prevent @drop="dropOnTray">
                <button v-for="wire in looseWires" :key="wire.id" type="button" class="wire-card" :class="{
                  selected: selectedWire?.id === wire.id
                }" draggable="true" @dragstart="dragWire(wire)" @dragend="draggedWire = null"
                  @click="selectWire(wire)">
                  <span class="wire-swatch" :class="wire.className"></span>

                  <span class="wire-card-text">
                    <strong>{{ wire.name }}</strong>
                    <small>{{ wire.short }}</small>
                  </span>

                  <i class="fas fa-grip-lines"></i>
                </button>

                <div v-if="looseWires.length === 0" class="tray-empty">
                  <i class="fas fa-check"></i>
                  All conductors placed
                </div>
              </div>
            </div>

            <!-- PIN AREA -->
            <div class="wire-section">
              <div class="section-label">
                RJ-45 pin positions
                <span>Pin 1 → Pin 8</span>
              </div>

              <div class="pin-board">
                <button v-for="(wire, index) in arrangedWires" :key="index" type="button" class="pin-slot" :class="{
                  filled: wire,
                  selected:
                    selectedWire?.id === wire?.id,
                  target:
                    selectedWire &&
                    !wire
                }" @dragover.prevent @drop.prevent.stop="dropWire(index)" @click="selectPin(index)">
                  <span class="pin-number">
                    {{ index + 1 }}
                  </span>

                  <span v-if="wire" class="pin-wire" :class="wire.className"></span>

                  <strong v-if="wire">
                    {{ wire.short }}
                  </strong>

                  <span v-else class="pin-placeholder">
                    Drop
                  </span>
                </button>
              </div>
            </div>

            <!-- CURRENT ORDER -->
            <div class="current-order">
              <div class="section-label">
                Your current order
              </div>

              <div class="order-row">
                <span v-for="(wire, index) in arrangedWires" :key="index" class="order-item" :class="{ empty: !wire }">
                  {{ wire?.short || "—" }}
                </span>
              </div>
            </div>

            <div class="assessment-note">
              <i class="fas fa-eye-slash"></i>

              <span>
                The correct pinout is checked by the continuity tester.
              </span>
            </div>

            <div class="action-row">
              <button type="button" class="action-btn primary" :disabled="!arrangedComplete" @click="finishWiring">
                <i class="fas fa-plug"></i>
                Prepare cable
              </button>
            </div>
          </div>

          <!-- ================= STAGE 3 ================= -->
          <div v-else-if="stage === 3" class="stage-content">

            <div class="task-banner">
              <div class="task-banner-icon">
                <i class="fas fa-plug"></i>
              </div>

              <div>
                <strong>Insert and crimp the connector</strong>
                <span>
                  Move the prepared cable into the RJ-45 plug,
                  then crimp it.
                </span>
              </div>
            </div>

            <div class="termination-workspace">

              <!-- CABLE -->
              <div class="termination-item" :class="{
                dragging: wireBundleDragged,
                completed: wireBundleInserted
              }">
                <span class="workspace-label">
                  PREPARED CABLE
                </span>

                <div class="prepared-cable" draggable="true" @dragstart="dragWireBundle"
                  @dragend="wireBundleDragged = false" @click="insertWireBundle">
                  <div class="cable-jacket-large">
                    <span v-for="n in 3" :key="n" class="jacket-line"></span>
                  </div>

                  <div class="cable-exposed">
                    <span v-for="wire in arrangedWires" :key="wire.id" class="prepared-wire"
                      :class="wire.className"></span>
                  </div>
                </div>

                <small>
                  {{
                    wireBundleInserted
                      ? "Cable inserted"
                      : "Drag or tap cable"
                  }}
                </small>
              </div>

              <!-- ARROW -->
              <div class="workspace-arrow">
                <i class="fas fa-arrow-right"></i>
              </div>

              <!-- PLUG -->
              <div class="termination-item connector-item" :class="{
                highlight: wireBundleDragged,
                seated: wireBundleInserted
              }" @dragover.prevent @drop.prevent="dropWireBundle" @click="insertWireBundle">
                <span class="workspace-label">
                  RJ-45 CONNECTOR
                </span>

                <div class="rj45-connector">

                  <div class="connector-top">
                    <div v-for="n in 8" :key="n" class="contact">
                      <span>{{ n }}</span>
                    </div>
                  </div>

                  <div class="connector-opening">
                    <div v-if="wireBundleInserted" class="inserted-bundle">
                      <span v-for="wire in arrangedWires" :key="wire.id" class="inserted-wire"
                        :class="wire.className"></span>
                    </div>

                    <span v-else class="opening-text">
                      INSERT
                    </span>
                  </div>

                  <div class="connector-latch">
                    <span>LATCH</span>
                  </div>
                </div>

                <small>
                  {{
                    wireBundleInserted
                      ? "8 conductors seated"
                      : "Drop cable here"
                  }}
                </small>
              </div>
            </div>

            <!-- SHIELD -->
            <div class="option-row">
              <button type="button" class="option-btn" :class="{ active: shielded }" @click="shielded = !shielded">
                <i class="fas fa-shield-halved"></i>

                {{
                  shielded
                    ? "STP shield enabled"
                    : "UTP / unshielded"
                }}
              </button>

              <span class="option-help">
                Optional connector shell
              </span>
            </div>

            <!-- CRIMPER -->
            <div class="crimp-area">

              <div class="crimp-status">
                <i :class="connectorInserted
                    ? 'fas fa-circle-check'
                    : 'fas fa-circle-info'
                  "></i>

                <div>
                  <strong>
                    {{
                      connectorInserted
                        ? "Connector crimped"
                        : wireBundleInserted
                          ? "Ready to crimp"
                          : "Insert the cable first"
                    }}
                  </strong>

                  <span>
                    {{
                      connectorInserted
                        ? "The RJ-45 contact is locked."
                        : wireBundleInserted
                          ? "Use the crimper to secure the connector."
                          : "Place all 8 conductors inside the plug."
                    }}
                  </span>
                </div>
              </div>

              <button type="button" class="crimper-btn" :class="{ ready: wireBundleInserted }" :disabled="!wireBundleInserted ||
                connectorInserted
                " @click="crimpConnector">
                <i class="fas fa-compress"></i>

                <span>
                  {{
                    connectorInserted
                      ? "Crimped"
                      : "Crimp connector"
                  }}
                </span>
              </button>
            </div>

            <!-- CONTINUE -->
            <div class="action-row">
              <button type="button" class="action-btn primary" :disabled="!connectorInserted" @click="stage = 4">
                <i class="fas fa-bolt"></i>
                Continue to tester
              </button>
            </div>
          </div>

          <!-- ================= STAGE 4 ================= -->
          <div v-else class="stage-content test-stage">

            <div class="task-banner">
              <div class="task-banner-icon">
                <i class="fas fa-bolt"></i>
              </div>

              <div>
                <strong>Test continuity</strong>
                <span>
                  Connect the cable and scan all eight pins.
                </span>
              </div>
            </div>

            <!-- TESTER -->
            <div class="tester">
              <div class="tester-header">
                <div>
                  <span class="eyebrow">Digital tester</span>
                  <h4>LAN CABLE TESTER</h4>
                </div>

                <i class="fas fa-network-wired"></i>
              </div>

              <div class="tester-screen">
                {{ testResult || "READY / CONNECT CABLE" }}
              </div>

              <div class="tester-pins">
                <div v-for="(wire, index) in arrangedWires" :key="index" class="tester-pin" :class="{
                  lit: testLights.includes(index)
                }">
                  <span class="tester-wire" :class="wire?.className"></span>

                  <strong>{{ index + 1 }}</strong>
                </div>
              </div>
            </div>

            <div class="test-instructions">
              <strong>
                Required result
              </strong>

              <span>
                All eight pins must light in sequence and match
                T568{{ selectedStandard }}.
              </span>
            </div>

            <div class="action-row">
              <button type="button" class="action-btn primary" :disabled="tested || testing" @click="runTest">
                <i :class="testing
                    ? 'fas fa-spinner fa-spin'
                    : 'fas fa-bolt'
                  "></i>

                {{
                  testing
                    ? "Testing..."
                    : tested
                      ? "Test complete"
                      : "Run continuity test"
                }}
              </button>
            </div>

            <!-- RESULT -->
            <div v-if="tested" class="result-card" :class="{
              success: testPassed,
              failed: !testPassed
            }">
              <div class="result-icon">
                <i :class="testPassed
                    ? 'fas fa-circle-check'
                    : 'fas fa-circle-xmark'
                  "></i>
              </div>

              <div class="result-content">
                <strong>
                  {{
                    testPassed
                      ? "Cable passed"
                      : "Cable failed"
                  }}
                </strong>

                <span>
                  {{
                    testPassed
                      ? `Pins 1–8 match T568${selectedStandard}.`
                      : `The conductor sequence does not match T568${selectedStandard}.`
                  }}
                </span>
              </div>
            </div>

            <!-- RETRY -->
            <div v-if="tested && !testPassed" class="retry-box">
              <span>
                Correct the wire arrangement and test the cable again.
              </span>

              <button type="button" class="secondary-btn" @click="retryWiring">
                <i class="fas fa-arrow-left"></i>
                Return to wiring
              </button>
            </div>
          </div>
        </section>
      </section>

      <!-- FOOTER ACTIONS -->
      <section class="assessment-actions">

        <button type="button" class="reset-btn" @click="resetBench">
          <i class="fas fa-rotate-left"></i>
          Reset activity
        </button>

        <button type="button" class="submit-btn" :disabled="!testPassed" @click="finishActivity">
          Complete activity
          <i class="fas fa-arrow-right"></i>
        </button>
      </section>
    </main>

    <!-- COMPLETE -->
    <div v-if="completed" class="completion-notice">
      <div class="completion-icon">
        <i class="fas fa-certificate"></i>
      </div>

      <div>
        <strong>Activity complete</strong>
        <span>
          RJ-45 cable passed continuity testing.
        </span>
      </div>

      <button type="button" @click="
        router.push(
          `/user/quizzes/assessment/${quizId}`
        )
        ">
        Return
      </button>
    </div>
  </div>
</template>

<script setup>
import {
  computed,
  onUnmounted,
  ref
} from "vue";

import {
  useRoute,
  useRouter
} from "vue-router";

import {
  rj45HandsOnRequest
} from "@/assessmentrequests/rj45HandsOn";

/* ROUTER */
const router = useRouter();
const route = useRoute();
const quizId = route.params.id;

/* GENERAL */
const stage = ref(1);
const completed = ref(false);

const tested = ref(false);
const testPassed = ref(false);
const testResult = ref("");
const testing = ref(false);
const testLights = ref([]);

let testTimer = null;

/* STAGE 1 */
const cableCut = ref(false);
const scissorDragged = ref(false);

/* STAGE 2 */
const selectedWire = ref(null);
const draggedWire = ref(null);

/* STAGE 3 */
const wireBundleDragged = ref(false);
const wireBundleInserted = ref(false);
const connectorInserted = ref(false);
const shielded = ref(false);

/* WIRING STANDARDS */
const wireSets = {
  A: [
    {
      id: "wg",
      name: "White-Green",
      short: "WG",
      className: "white-green"
    },
    {
      id: "g",
      name: "Green",
      short: "G",
      className: "green"
    },
    {
      id: "wo",
      name: "White-Orange",
      short: "WO",
      className: "white-orange"
    },
    {
      id: "b",
      name: "Blue",
      short: "B",
      className: "blue"
    },
    {
      id: "wb",
      name: "White-Blue",
      short: "WB",
      className: "white-blue"
    },
    {
      id: "o",
      name: "Orange",
      short: "O",
      className: "orange"
    },
    {
      id: "wbr",
      name: "White-Brown",
      short: "WBr",
      className: "white-brown"
    },
    {
      id: "br",
      name: "Brown",
      short: "Br",
      className: "brown"
    }
  ],

  B: [
    {
      id: "wo",
      name: "White-Orange",
      short: "WO",
      className: "white-orange"
    },
    {
      id: "o",
      name: "Orange",
      short: "O",
      className: "orange"
    },
    {
      id: "wg",
      name: "White-Green",
      short: "WG",
      className: "white-green"
    },
    {
      id: "b",
      name: "Blue",
      short: "B",
      className: "blue"
    },
    {
      id: "wb",
      name: "White-Blue",
      short: "WB",
      className: "white-blue"
    },
    {
      id: "g",
      name: "Green",
      short: "G",
      className: "green"
    },
    {
      id: "wbr",
      name: "White-Brown",
      short: "WBr",
      className: "white-brown"
    },
    {
      id: "br",
      name: "Brown",
      short: "Br",
      className: "brown"
    }
  ]
};

/* STANDARD */
const selectedStandard = ref("B");

const targetOrder = computed(() => {
  return wireSets[selectedStandard.value];
});

/* PROCEDURE */
const procedureSteps = [
  {
    id: 1,
    title: "Cut and strip",
    detail: "Prepare the cable"
  },
  {
    id: 2,
    title: "Arrange wires",
    detail: "Place pins 1–8"
  },
  {
    id: 3,
    title: "Insert and crimp",
    detail: "Secure connector"
  },
  {
    id: 4,
    title: "Test continuity",
    detail: "Verify the cable"
  }
];

/* WIRES */
const looseWires = ref(
  [...wireSets.B].sort(() => Math.random() - 0.5)
);

const arrangedWires = ref(
  Array(8).fill(null)
);

/* COMPUTED */
const currentStep = computed(() => {
  return procedureSteps[stage.value - 1];
});

const progress = computed(() => {
  return stage.value * 25;
});

const arrangedComplete = computed(() => {
  return arrangedWires.value.every(Boolean);
});

const isWiringCorrect = computed(() => {
  if (!arrangedComplete.value) {
    return false;
  }

  return arrangedWires.value.every(
    (wire, index) =>
      wire?.id === targetOrder.value[index]?.id
  );
});

/* PROCEDURE NAVIGATION */
const goToCompletedStage = (requestedStage) => {
  if (requestedStage < stage.value) {
    stage.value = requestedStage;
  }
};

/* STAGE 1 */
const chooseStandard = (standard) => {
  if (stage.value !== 1) {
    return;
  }

  selectedStandard.value = standard;

  arrangedWires.value = Array(8).fill(null);

  looseWires.value = [
    ...wireSets[standard]
  ].sort(() => Math.random() - 0.5);
};

const cutCable = () => {
  if (cableCut.value) {
    return;
  }

  cableCut.value = true;

  window.setTimeout(() => {
    stage.value = 2;
  }, 500);
};

const dragScissors = () => {
  scissorDragged.value = true;
};

const dropScissors = () => {
  if (scissorDragged.value) {
    cutCable();
  }

  scissorDragged.value = false;
};

/* STAGE 2 */
const selectWire = (wire) => {
  selectedWire.value =
    selectedWire.value?.id === wire.id
      ? null
      : wire;
};

const dragWire = (wire) => {
  selectedWire.value = wire;
  draggedWire.value = wire;
};

const returnWireToTray = (wire) => {
  if (
    wire &&
    !looseWires.value.some(
      item => item.id === wire.id
    )
  ) {
    looseWires.value.push(wire);
  }
};

const dropOnTray = () => {
  const wire =
    draggedWire.value ||
    selectedWire.value;

  if (!wire) {
    return;
  }

  const oldIndex =
    arrangedWires.value.findIndex(
      item => item?.id === wire.id
    );

  if (oldIndex >= 0) {
    arrangedWires.value[oldIndex] = null;
  }

  returnWireToTray(wire);

  selectedWire.value = null;
  draggedWire.value = null;
};

const dropWire = (index) => {
  const wire =
    draggedWire.value ||
    selectedWire.value;

  if (!wire) {
    return;
  }

  const oldIndex =
    arrangedWires.value.findIndex(
      item => item?.id === wire.id
    );

  if (oldIndex >= 0) {
    arrangedWires.value[oldIndex] = null;
  }

  const displaced =
    arrangedWires.value[index];

  arrangedWires.value[index] = wire;

  if (
    displaced &&
    displaced.id !== wire.id
  ) {
    returnWireToTray(displaced);
  }

  looseWires.value =
    looseWires.value.filter(
      item => item.id !== wire.id
    );

  selectedWire.value = null;
  draggedWire.value = null;
};

const selectPin = (index) => {
  if (selectedWire.value) {
    dropWire(index);
    return;
  }

  if (arrangedWires.value[index]) {
    selectedWire.value =
      arrangedWires.value[index];
  }
};

const finishWiring = () => {
  if (!arrangedComplete.value) {
    return;
  }

  stage.value = 3;
};

/* STAGE 3 */
const dragWireBundle = () => {
  if (connectorInserted.value) {
    return;
  }

  wireBundleDragged.value = true;
};

const dropWireBundle = () => {
  if (
    !wireBundleDragged.value ||
    connectorInserted.value
  ) {
    return;
  }

  wireBundleInserted.value = true;
  wireBundleDragged.value = false;
};

const insertWireBundle = () => {
  if (
    connectorInserted.value ||
    !arrangedComplete.value
  ) {
    return;
  }

  wireBundleInserted.value = true;
  wireBundleDragged.value = false;
};

const crimpConnector = () => {
  if (
    !wireBundleInserted.value ||
    connectorInserted.value
  ) {
    return;
  }

  connectorInserted.value = true;
};

/* STAGE 4 */
const runTest = () => {
  if (testing.value || tested.value) {
    return;
  }

  testing.value = true;
  testLights.value = [];
  testResult.value = "";

  let light = 0;

  testTimer = window.setInterval(() => {
    testLights.value.push(light);

    light += 1;

    if (light === 8) {
      window.clearInterval(testTimer);

      testTimer = null;
      testing.value = false;
      tested.value = true;

      testPassed.value =
        isWiringCorrect.value;

      testResult.value =
        isWiringCorrect.value
          ? "PASS / 1 2 3 4 5 6 7 8"
          : "FAIL / PINOUT ERROR";
    }
  }, 220);
};

/* RETRY */
const retryWiring = () => {
  if (testTimer) {
    window.clearInterval(testTimer);
    testTimer = null;
  }

  tested.value = false;
  testPassed.value = false;
  testResult.value = "";
  testing.value = false;
  testLights.value = [];

  wireBundleInserted.value = false;
  connectorInserted.value = false;

  stage.value = 2;
};

/* RESET */
const resetBench = () => {
  if (testTimer) {
    window.clearInterval(testTimer);
    testTimer = null;
  }

  stage.value = 1;
  completed.value = false;

  tested.value = false;
  testPassed.value = false;
  testResult.value = "";
  testing.value = false;
  testLights.value = [];

  cableCut.value = false;
  scissorDragged.value = false;

  selectedWire.value = null;
  draggedWire.value = null;

  wireBundleDragged.value = false;
  wireBundleInserted.value = false;
  connectorInserted.value = false;

  shielded.value = false;

  arrangedWires.value =
    Array(8).fill(null);

  looseWires.value = [
    ...wireSets[selectedStandard.value]
  ].sort(() => Math.random() - 0.5);
};

/* COMPLETE */
const finishActivity = () => {
  if (!testPassed.value) {
    return;
  }

  completed.value = true;

  rj45HandsOnRequest
    .complete({
      quiz_id: quizId,
      standard: selectedStandard.value,
      passed: true
    })
    .catch((error) => {
      console.error(
        "Failed to record RJ-45 assessment:",
        error
      );
    });
};

/* CLEANUP */
onUnmounted(() => {
  if (testTimer) {
    window.clearInterval(testTimer);
  }
});
</script>

<style scoped>
/* =========================================================
   BASE
========================================================= */

.rj45-page {
  --black: #000;
  --white: #fff;

  --gray-50: #fafafa;
  --gray-100: #f5f5f5;
  --gray-200: #ebebeb;
  --gray-300: #d3d3d3;
  --gray-400: #a9a9a9;
  --gray-500: #696969;
  --gray-600: #444;

  min-height: 100vh;
  padding-bottom: 50px;

  background:
    linear-gradient(rgba(211, 211, 211, 0.55) 1px,
      transparent 1px),
    linear-gradient(90deg,
      rgba(211, 211, 211, 0.55) 1px,
      transparent 1px),
    #f4f4f2;

  background-size: 28px 28px;

  color: var(--black);

  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    sans-serif;
}

/* =========================================================
   HEADER
========================================================= */

.assessment-header {
  position: sticky;
  top: 0;
  z-index: 20;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 24px;

  padding: 14px 24px;

  background: rgba(255, 255, 255, 0.95);

  border-bottom: 1px solid var(--gray-300);

  backdrop-filter: blur(18px);
}

.header-left {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 12px;
}

.back-btn {
  width: 38px;
  height: 38px;

  display: grid;
  place-items: center;

  flex-shrink: 0;

  border: 1px solid var(--gray-300);
  border-radius: 9px;

  background: var(--white);

  color: var(--gray-500);

  cursor: pointer;

  transition: 0.2s ease;
}

.back-btn:hover {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

.assessment-label,
.eyebrow {
  display: block;

  color: var(--gray-500);

  font-size: 9px;
  font-weight: 800;

  letter-spacing: 0.1em;

  text-transform: uppercase;
}

.header-left h1 {
  margin: 3px 0 0;

  font-size: 18px;
  line-height: 1.2;
}

.progress-info {
  width: 220px;
  flex-shrink: 0;
}

.progress-top {
  display: flex;
  justify-content: space-between;

  color: var(--gray-500);

  font-size: 10px;
  font-weight: 800;
}

.progress-bar {
  height: 5px;

  margin-top: 7px;

  overflow: hidden;

  border-radius: 999px;

  background: var(--gray-200);
}

.progress-fill {
  height: 100%;

  background: var(--black);

  border-radius: inherit;

  transition: width 0.3s ease;
}

/* =========================================================
   MAIN
========================================================= */

.assessment-container {
  width: min(1080px, calc(100% - 32px));

  margin: 24px auto 0;
}

/* =========================================================
   SCENARIO
========================================================= */

.scenario-card {
  padding: 22px 24px;

  border: 1px solid var(--black);
  border-radius: 14px;

  background: var(--black);

  color: var(--white);

  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
}

.scenario-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 10px;
}

.scenario-kicker {
  color: #d3d3d3;

  font-size: 9px;
  font-weight: 800;

  letter-spacing: 0.1em;

  text-transform: uppercase;
}

.scenario-kicker i {
  margin-right: 5px;
}

.ticket-badge {
  padding: 5px 8px;

  border: 1px solid #4b4b4b;
  border-radius: 6px;

  color: #aaa;

  font-size: 8px;
  font-weight: 800;

  letter-spacing: 0.08em;
}

.scenario-card h2 {
  max-width: 780px;

  margin: 12px 0 0;

  font-size: clamp(18px, 2vw, 24px);

  line-height: 1.35;
}

.scenario-card p {
  max-width: 760px;

  margin: 9px 0 0;

  color: #bcbcbc;

  font-size: 11px;

  line-height: 1.6;
}

/* =========================================================
   BENCH
========================================================= */

.bench-layout {
  display: grid;

  grid-template-columns:
    250px minmax(0, 1fr);

  gap: 12px;

  margin-top: 12px;
}

.procedure-panel,
.work-surface {
  background: var(--white);

  border: 1px solid var(--gray-300);
  border-radius: 14px;

  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.05);
}

.procedure-panel {
  padding: 16px;
}

.work-surface {
  min-width: 0;
  padding: 18px;
}

.panel-heading,
.surface-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 10px;
}

.panel-heading h3,
.surface-header h3 {
  margin: 4px 0 15px;

  font-size: 13px;
}

.bench-code,
.stage-badge {
  padding: 6px 8px;

  border: 1px solid var(--gray-300);
  border-radius: 7px;

  color: var(--gray-500);

  font-size: 8px;
  font-weight: 900;

  letter-spacing: 0.06em;
}

/* =========================================================
   PROCEDURE
========================================================= */

.procedure-step {
  width: 100%;

  display: flex;
  align-items: center;

  gap: 9px;

  margin-bottom: 7px;

  padding: 9px;

  border: 1px solid var(--gray-200);
  border-radius: 9px;

  background: var(--gray-50);

  color: var(--gray-500);

  text-align: left;

  font: inherit;

  cursor: pointer;

  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    color 0.2s ease;
}

.procedure-step.current {
  border-color: var(--black);

  background: var(--white);

  color: var(--black);
}

.procedure-step.complete {
  background: var(--gray-100);

  color: var(--black);
}

.procedure-step:disabled {
  opacity: 0.45;

  cursor: not-allowed;
}

.step-icon {
  width: 25px;
  height: 25px;

  flex-shrink: 0;

  display: grid;
  place-items: center;

  border-radius: 7px;

  background: var(--black);

  color: var(--white);

  font-size: 9px;
  font-weight: 900;
}

.complete .step-icon {
  background: var(--gray-200);
  color: var(--black);
}

.step-text {
  min-width: 0;
  flex: 1;
}

.procedure-step strong,
.procedure-step small {
  display: block;
}

.procedure-step strong {
  font-size: 9px;
}

.procedure-step small {
  margin-top: 2px;

  color: var(--gray-400);

  font-size: 8px;
}

.procedure-step>i {
  font-size: 7px;
}

.standard-note {
  display: flex;

  gap: 8px;

  margin-top: 14px;
  padding-top: 13px;

  border-top: 1px solid var(--gray-200);

  color: var(--gray-500);

  font-size: 9px;

  line-height: 1.5;
}

.standard-note i {
  color: var(--black);
}

/* =========================================================
   STAGE
========================================================= */

.stage-content {
  min-height: 500px;

  display: flex;
  flex-direction: column;

  gap: 18px;
}

.instruction {
  padding: 12px 14px;

  border: 1px solid var(--gray-200);
  border-radius: 10px;

  background: var(--gray-50);
}

.instruction-title {
  display: flex;
  align-items: flex-start;

  gap: 10px;
}

.instruction-number {
  width: 24px;
  height: 24px;

  flex-shrink: 0;

  display: grid;
  place-items: center;

  border-radius: 7px;

  background: var(--black);

  color: var(--white);

  font-size: 9px;
  font-weight: 900;
}

.instruction-title strong,
.instruction-title span {
  display: block;
}

.instruction-title strong {
  font-size: 11px;
}

.instruction-title span {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 9px;

  line-height: 1.5;
}

/* =========================================================
   TASK BANNER
========================================================= */

.task-banner {
  display: flex;
  align-items: center;

  gap: 10px;

  padding: 12px 14px;

  border: 1px solid var(--gray-300);
  border-radius: 10px;

  background: var(--gray-50);
}

.task-banner-icon {
  width: 32px;
  height: 32px;

  flex-shrink: 0;

  display: grid;
  place-items: center;

  border-radius: 8px;

  background: var(--black);

  color: var(--white);
}

.task-banner strong,
.task-banner span {
  display: block;
}

.task-banner strong {
  font-size: 11px;
}

.task-banner span {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 9px;
}

/* =========================================================
   STAGE 1
========================================================= */

.standard-options {
  display: grid;

  grid-template-columns: 1fr 1fr;

  gap: 8px;
}

.standard-card {
  padding: 12px;

  border: 1px solid var(--gray-300);
  border-radius: 9px;

  background: var(--white);

  text-align: left;

  cursor: pointer;

  transition: 0.2s ease;
}

.standard-card:hover {
  border-color: var(--black);
}

.standard-card.selected {
  border-color: var(--black);

  background: var(--black);

  color: var(--white);
}

.standard-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.standard-card-top strong {
  font-size: 12px;
}

.standard-card-top i {
  font-size: 13px;
}

.standard-card>span {
  display: block;

  margin-top: 5px;

  color: var(--gray-500);

  font-size: 8px;
}

.standard-card.selected>span {
  color: #c8c8c8;
}

/* CABLE AREA */

.cable-work-area {
  min-height: 220px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 28px;

  padding: 24px;

  border: 1px solid var(--gray-200);
  border-radius: 12px;

  background:
    linear-gradient(135deg,
      #fafafa,
      #f0f0f0);
}

.cut-target {
  position: relative;

  width: min(390px, 75%);

  display: flex;
  align-items: center;

  transition: 0.2s ease;
}

.cut-target.active {
  transform: scale(1.02);
}

.cable-body {
  width: 100%;
  height: 34px;

  position: relative;

  border: 2px solid var(--gray-500);
  border-radius: 999px;

  background:
    linear-gradient(180deg,
      #eeeeee,
      #cfcfcf,
      #a9a9a9);

  transition: width 0.45s ease;
}

.cable-body.stripped {
  width: 68%;
}

.cable-ring {
  position: absolute;
  top: 4px;
  bottom: 4px;

  width: 2px;

  background: rgba(105, 105, 105, 0.3);
}

.cable-ring:nth-child(1) {
  left: 25%;
}

.cable-ring:nth-child(2) {
  left: 50%;
}

.cable-ring:nth-child(3) {
  left: 75%;
}

.cut-line {
  position: absolute;

  left: 68%;

  top: -20px;
  bottom: -20px;

  z-index: 2;

  border-left: 2px dashed var(--black);
}

.cut-target-label {
  position: absolute;

  left: 68%;

  top: -35px;

  transform: translateX(-50%);

  font-size: 7px;
  font-weight: 900;

  letter-spacing: 0.08em;
}

.exposed-wires-preview {
  width: 84px;

  display: flex;

  gap: 2px;

  margin-left: -2px;

  opacity: 0;

  transform: translateX(-10px);

  transition: 0.35s ease;
}

.exposed-wires-preview.visible {
  opacity: 1;

  transform: translateX(0);
}

.preview-wire {
  width: 5px;
  height: 48px;

  border-radius: 4px;
}

.tool-card {
  width: 110px;

  display: flex;
  align-items: center;

  gap: 9px;

  padding: 11px;

  border: 1px solid var(--gray-300);
  border-radius: 10px;

  background: var(--white);

  color: var(--black);

  cursor: grab;

  text-align: left;
}

.tool-card:hover {
  border-color: var(--black);
}

.tool-card i {
  font-size: 20px;
}

.tool-card strong,
.tool-card small {
  display: block;
}

.tool-card strong {
  font-size: 9px;
}

.tool-card small {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 7px;
}

/* =========================================================
   STAGE 2
========================================================= */

.wire-section {
  display: grid;

  gap: 7px;
}

.section-label {
  display: flex;
  justify-content: space-between;

  color: var(--gray-500);

  font-size: 8px;
  font-weight: 900;

  text-transform: uppercase;

  letter-spacing: 0.06em;
}

.section-label span {
  color: var(--gray-400);
}

.wire-tray {
  min-height: 104px;

  display: grid;

  grid-template-columns:
    repeat(4, minmax(0, 1fr));

  gap: 7px;

  padding: 10px;

  border: 1px dashed var(--gray-300);
  border-radius: 10px;

  background: var(--gray-50);
}

.wire-card {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 6px;

  padding: 8px;

  border: 1px solid var(--gray-300);
  border-radius: 8px;

  background: var(--white);

  color: var(--black);

  text-align: left;

  cursor: grab;

  transition: 0.15s ease;
}

.wire-card:hover {
  border-color: var(--black);
}

.wire-card.selected {
  border: 2px solid var(--black);

  box-shadow:
    0 0 0 2px rgba(0, 0, 0, 0.08);
}

.wire-swatch {
  width: 9px;
  height: 30px;

  flex-shrink: 0;

  border-radius: 4px;
}

.wire-card-text {
  min-width: 0;
  flex: 1;
}

.wire-card-text strong,
.wire-card-text small {
  display: block;
}

.wire-card-text strong {
  overflow: hidden;

  text-overflow: ellipsis;
  white-space: nowrap;

  font-size: 8px;
}

.wire-card-text small {
  margin-top: 2px;

  color: var(--gray-400);

  font-size: 7px;
}

.wire-card>i {
  color: var(--gray-400);

  font-size: 8px;
}

.tray-empty {
  grid-column: 1 / -1;

  display: grid;
  place-items: center;

  color: var(--gray-500);

  font-size: 9px;
  font-weight: 800;
}

.pin-board {
  display: grid;

  grid-template-columns:
    repeat(8, minmax(0, 1fr));

  gap: 6px;

  padding: 10px;

  border: 1px solid var(--gray-300);
  border-radius: 10px;

  background: var(--gray-50);
}

.pin-slot {
  min-height: 86px;

  display: flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 7px;

  padding: 5px 3px;

  border: 1px dashed var(--gray-300);
  border-radius: 8px;

  background: var(--white);

  cursor: pointer;

  transition: 0.15s ease;
}

.pin-slot:hover,
.pin-slot.target {
  border-color: var(--black);
  background: #f8f8f8;
}

.pin-slot.selected {
  border: 2px solid var(--black);

  box-shadow:
    0 0 0 2px rgba(0, 0, 0, 0.08);
}

.pin-number {
  color: var(--gray-400);

  font-size: 8px;
  font-weight: 900;
}

.pin-wire {
  width: 11px;
  height: 40px;

  border-radius: 6px;
}

.pin-slot strong {
  font-size: 8px;
}

.pin-placeholder {
  color: var(--gray-400);

  font-size: 7px;
}

.current-order {
  display: grid;

  gap: 7px;
}

.order-row {
  display: grid;

  grid-template-columns:
    repeat(8, 1fr);

  gap: 5px;
}

.order-item {
  display: grid;
  place-items: center;

  min-height: 27px;

  border: 1px solid var(--gray-300);
  border-radius: 6px;

  background: var(--gray-100);

  font-size: 8px;
  font-weight: 900;
}

.order-item.empty {
  color: var(--gray-400);
}

.assessment-note {
  display: flex;
  align-items: center;

  gap: 8px;

  padding: 9px 10px;

  border-radius: 8px;

  background: var(--gray-100);

  color: var(--gray-500);

  font-size: 8px;
}

.assessment-note i {
  color: var(--black);
}

/* =========================================================
   STAGE 3
========================================================= */

.termination-workspace {
  min-height: 310px;

  display: grid;

  grid-template-columns:
    1fr 80px 1fr;

  align-items: center;

  gap: 8px;

  padding: 18px;

  border: 1px solid var(--gray-200);
  border-radius: 12px;

  background:
    linear-gradient(135deg,
      #fafafa,
      #f1f1f1);
}

.termination-item {
  min-width: 0;

  min-height: 240px;

  display: flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 10px;

  padding: 14px;

  border: 1px dashed var(--gray-300);
  border-radius: 11px;

  background: rgba(255, 255, 255, 0.7);

  transition: 0.2s ease;
}

.termination-item.highlight {
  border: 2px solid var(--black);

  background: var(--white);

  box-shadow:
    0 0 0 4px rgba(0, 0, 0, 0.05);
}

.termination-item.seated {
  border-color: var(--black);

  background: #fafafa;
}

.termination-item.completed {
  opacity: 0.65;
}

.workspace-label {
  color: var(--gray-400);

  font-size: 7px;
  font-weight: 900;

  letter-spacing: 0.09em;
}

.termination-item small {
  color: var(--gray-500);

  font-size: 8px;
  font-weight: 800;
}

/* CABLE */

.prepared-cable {
  display: flex;
  align-items: center;

  cursor: grab;

  user-select: none;
}

.cable-jacket-large {
  width: 70px;
  height: 48px;

  position: relative;

  border: 2px solid var(--gray-500);
  border-right: none;

  border-radius: 20px 0 0 20px;

  background:
    linear-gradient(180deg,
      #eee,
      #ccc,
      #a9a9a9);
}

.jacket-line {
  position: absolute;

  top: 5px;
  bottom: 5px;

  width: 2px;

  background: rgba(105, 105, 105, 0.3);
}

.jacket-line:nth-child(1) {
  left: 30%;
}

.jacket-line:nth-child(2) {
  left: 60%;
}

.jacket-line:nth-child(3) {
  left: 80%;
}

.cable-exposed {
  display: flex;

  align-items: center;

  gap: 2px;

  padding: 4px 0;
}

.prepared-wire {
  width: 6px;
  height: 58px;

  border-radius: 4px;
}

/* ARROW */

.workspace-arrow {
  display: grid;

  place-items: center;

  color: var(--gray-400);

  font-size: 22px;
}

/* CONNECTOR */

.rj45-connector {
  width: 190px;

  position: relative;

  display: grid;

  gap: 8px;
}

.connector-top {
  display: grid;

  grid-template-columns:
    repeat(8, 1fr);

  gap: 2px;

  padding: 6px;

  border: 1px solid #b9b9b9;
  border-radius: 5px 5px 2px 2px;

  background:
    linear-gradient(180deg,
      #f7f7f7,
      #d9d9d9);
}

.contact {
  height: 22px;

  display: grid;
  place-items: flex-end;

  border: 1px solid #bcaa66;
  border-radius: 2px;

  background:
    linear-gradient(180deg,
      #f8e9a7,
      #c7a22c);
}

.contact span {
  margin-bottom: -9px;

  color: #6c5a21;

  font-size: 6px;
  font-weight: 900;
}

.connector-opening {
  min-height: 64px;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 8px;

  border: 1px solid #8e9b9d;
  border-radius: 4px 4px 10px 10px;

  background:
    linear-gradient(180deg,
      #dce5e7,
      #b9c7ca);
}

.opening-text {
  color: #667274;

  font-size: 7px;
  font-weight: 900;

  letter-spacing: 0.1em;
}

.inserted-bundle {
  display: flex;

  gap: 2px;
}

.inserted-wire {
  width: 5px;
  height: 48px;

  border-radius: 3px;
}

.connector-latch {
  width: 72px;
  height: 27px;

  margin: -1px auto 0;

  display: grid;
  place-items: center;

  border: 1px solid #a8b2b4;
  border-top: none;

  background:
    linear-gradient(180deg,
      #dfe5e7,
      #b4c0c3);

  clip-path:
    polygon(8% 0,
      92% 0,
      100% 55%,
      80% 100%,
      20% 100%,
      0 55%);
}

.connector-latch span {
  color: #596466;

  font-size: 6px;
  font-weight: 900;
}

/* SHIELD */

.option-row {
  display: flex;
  align-items: center;

  gap: 9px;
}

.option-btn {
  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding: 8px 10px;

  border: 1px solid var(--gray-300);
  border-radius: 8px;

  background: var(--gray-100);

  color: var(--gray-500);

  font: inherit;

  font-size: 8px;
  font-weight: 800;

  cursor: pointer;
}

.option-btn.active {
  border-color: var(--black);

  background: var(--black);

  color: var(--white);
}

.option-help {
  color: var(--gray-400);

  font-size: 8px;
}

/* CRIMP */

.crimp-area {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;

  padding: 12px;

  border: 1px solid var(--gray-200);
  border-radius: 10px;

  background: var(--gray-50);
}

.crimp-status {
  display: flex;
  align-items: center;

  gap: 9px;
}

.crimp-status>i {
  color: var(--black);

  font-size: 17px;
}

.crimp-status strong,
.crimp-status span {
  display: block;
}

.crimp-status strong {
  font-size: 10px;
}

.crimp-status span {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 8px;
}

.crimper-btn {
  min-width: 120px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 7px;

  padding: 10px 12px;

  border: 1px solid var(--black);
  border-radius: 8px;

  background: var(--black);

  color: var(--white);

  font: inherit;

  font-size: 8px;
  font-weight: 900;

  cursor: pointer;
}

.crimper-btn:disabled {
  opacity: 0.3;

  cursor: not-allowed;
}

.crimper-btn.ready:not(:disabled) {
  animation: crimper-ready 1.3s ease-in-out infinite;
}

/* =========================================================
   STAGE 4
========================================================= */

.tester {
  padding: 18px;

  border: 1px solid var(--black);
  border-radius: 11px;

  background: var(--black);

  color: var(--white);
}

.tester-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tester-header h4 {
  margin: 4px 0 0;

  font-size: 12px;
}

.tester-header>i {
  color: #bdbdbd;

  font-size: 19px;
}

.tester-screen {
  margin-top: 15px;

  padding: 13px;

  background: #232323;

  color: #fff;

  font-family: monospace;

  font-size: 11px;

  text-align: center;

  border: 1px solid #383838;
}

.tester-pins {
  display: grid;

  grid-template-columns:
    repeat(8, 1fr);

  gap: 7px;

  margin-top: 16px;
}

.tester-pin {
  display: grid;

  justify-items: center;

  gap: 5px;

  padding: 8px 5px;

  border-radius: 6px;

  background: #222;
}

.tester-pin strong {
  color: #888;

  font-size: 8px;
}

.tester-wire {
  width: 13px;
  height: 30px;

  border-radius: 6px;

  opacity: 0.25;

  transition: 0.2s ease;
}

.tester-pin.lit {
  background: #fff;
}

.tester-pin.lit strong {
  color: #000;
}

.tester-pin.lit .tester-wire {
  opacity: 1;

  box-shadow:
    0 0 10px rgba(255, 255, 255, 0.9);
}

.test-instructions {
  display: grid;

  gap: 4px;

  padding: 10px 12px;

  border: 1px solid var(--gray-200);
  border-radius: 9px;

  background: var(--gray-50);
}

.test-instructions strong {
  font-size: 10px;
}

.test-instructions span {
  color: var(--gray-500);

  font-size: 8px;

  line-height: 1.5;
}

/* =========================================================
   BUTTONS
========================================================= */

.action-row {
  display: flex;

  justify-content: flex-end;
}

.action-btn {
  min-height: 38px;

  display: inline-flex;

  align-items: center;

  gap: 7px;

  padding: 9px 14px;

  border: 1px solid var(--black);
  border-radius: 8px;

  background: var(--white);

  color: var(--black);

  font: inherit;

  font-size: 9px;
  font-weight: 900;

  cursor: pointer;

  transition: 0.2s ease;
}

.action-btn.primary {
  background: var(--black);

  color: var(--white);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.action-btn:disabled {
  opacity: 0.3;

  cursor: not-allowed;
}

.secondary-btn {
  padding: 8px 11px;

  border: 1px solid var(--gray-300);
  border-radius: 8px;

  background: var(--white);

  color: var(--black);

  font: inherit;

  font-size: 8px;
  font-weight: 800;

  cursor: pointer;
}

/* =========================================================
   RESULT
========================================================= */

.result-card {
  display: flex;
  align-items: center;

  gap: 10px;

  padding: 12px;

  border-radius: 9px;
}

.result-card.success {
  border: 1px solid var(--black);

  background: var(--gray-100);
}

.result-card.failed {
  border: 1px dashed var(--gray-500);

  background: var(--gray-50);
}

.result-icon {
  width: 30px;
  height: 30px;

  display: grid;
  place-items: center;

  border-radius: 50%;

  background: var(--black);

  color: var(--white);
}

.result-content strong,
.result-content span {
  display: block;
}

.result-content strong {
  font-size: 10px;
}

.result-content span {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 8px;
}

.retry-box {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 10px;

  padding: 10px;

  border: 1px solid var(--gray-300);
  border-radius: 9px;

  background: var(--gray-50);

  color: var(--gray-500);

  font-size: 8px;
}

/* =========================================================
   FOOTER ACTIONS
========================================================= */

.assessment-actions {
  display: flex;
  justify-content: space-between;

  gap: 10px;

  margin-top: 12px;
}

.reset-btn,
.submit-btn {
  min-height: 40px;

  display: inline-flex;

  align-items: center;

  gap: 7px;

  padding: 9px 14px;

  border-radius: 8px;

  font: inherit;

  font-size: 9px;
  font-weight: 900;

  cursor: pointer;
}

.reset-btn {
  border: 1px solid var(--gray-300);

  background: var(--white);

  color: var(--gray-500);
}

.submit-btn {
  border: 1px solid var(--black);

  background: var(--black);

  color: var(--white);
}

.submit-btn:disabled {
  opacity: 0.3;

  cursor: not-allowed;
}

/* =========================================================
   COMPLETION
========================================================= */

.completion-notice {
  position: fixed;

  right: 20px;
  bottom: 20px;

  z-index: 50;

  display: flex;
  align-items: center;

  gap: 10px;

  max-width: 350px;

  padding: 13px;

  border: 1px solid var(--black);
  border-radius: 10px;

  background: var(--white);

  box-shadow:
    0 15px 35px rgba(0, 0, 0, 0.18);
}

.completion-icon {
  width: 34px;
  height: 34px;

  display: grid;
  place-items: center;

  border-radius: 8px;

  background: var(--black);

  color: var(--white);

  flex-shrink: 0;
}

.completion-notice strong,
.completion-notice span {
  display: block;
}

.completion-notice strong {
  font-size: 10px;
}

.completion-notice span {
  margin-top: 3px;

  color: var(--gray-500);

  font-size: 8px;
}

.completion-notice button {
  margin-left: auto;

  padding: 8px 10px;

  border: 1px solid var(--black);
  border-radius: 7px;

  background: var(--black);

  color: var(--white);

  font: inherit;

  font-size: 8px;
  font-weight: 900;

  cursor: pointer;
}

/* =========================================================
   WIRE COLORS
========================================================= */

.white-orange {
  background:
    repeating-linear-gradient(135deg,
      #fff 0 4px,
      #d9a56b 4px 7px);
}

.orange {
  background: #e38b39;
}

.white-green {
  background:
    repeating-linear-gradient(135deg,
      #fff 0 4px,
      #7bb174 4px 7px);
}

.green {
  background: #4e9a55;
}

.blue {
  background: #4386c5;
}

.white-blue {
  background:
    repeating-linear-gradient(135deg,
      #fff 0 4px,
      #79a8d2 4px 7px);
}

.white-brown {
  background:
    repeating-linear-gradient(135deg,
      #fff 0 4px,
      #a87c55 4px 7px);
}

.brown {
  background: #8b5b3e;
}

/* =========================================================
   ANIMATIONS
========================================================= */

@keyframes crimper-ready {
  50% {
    transform: translateY(-2px);
  }
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 850px) {
  .bench-layout {
    grid-template-columns: 1fr;
  }

  .procedure-panel {
    order: 2;
  }

  .work-surface {
    order: 1;
  }

  .wire-tray {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

  .termination-workspace {
    grid-template-columns: 1fr;
  }

  .workspace-arrow {
    transform: rotate(90deg);
  }
}

@media (max-width: 650px) {
  .assessment-header {
    flex-direction: column;
    align-items: stretch;

    padding: 12px 14px;
  }

  .progress-info {
    width: 100%;
  }

  .assessment-container {
    width: calc(100% - 18px);

    margin-top: 10px;
  }

  .scenario-card {
    padding: 17px;
  }

  .standard-options {
    grid-template-columns: 1fr;
  }

  .cable-work-area {
    flex-direction: column;
  }

  .cut-target {
    width: 90%;
  }

  .wire-tray {
    grid-template-columns: 1fr;
  }

  .pin-board {
    grid-template-columns:
      repeat(4, minmax(0, 1fr));
  }

  .order-row {
    grid-template-columns:
      repeat(4, 1fr);
  }

  .crimp-area {
    flex-direction: column;
    align-items: stretch;
  }

  .crimper-btn {
    width: 100%;
  }

  .assessment-actions {
    flex-direction: column;
  }

  .reset-btn,
  .submit-btn {
    width: 100%;
    justify-content: center;
  }

  .completion-notice {
    left: 10px;
    right: 10px;
    bottom: 10px;

    max-width: none;
  }
}

@media (prefers-reduced-motion: reduce) {

  .progress-fill,
  .standard-card,
  .wire-card,
  .pin-slot,
  .action-btn {
    transition: none;
  }

  .crimper-btn.ready:not(:disabled) {
    animation: none;
  }
}
</style>
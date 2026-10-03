<template>
  <div class="hardware-page">
    <div class="page-shell">
      <header class="page-header">
        <button type="button" class="back-button" aria-label="Back" @click="goBack">←</button>

        <div class="heading-copy">
          <p class="project-line"><strong>CSS PREP</strong><span>/</span><span>Virtual Hands-On</span></p>
          <h1>{{ assessment.title || quiz.title || 'Computer Assembly and Disassembly' }}</h1>
          <p>{{ assessment.category || 'Computer Maintenance and Troubleshooting' }}</p>
        </div>

        <div class="performance-strip" aria-label="Attempt status">
          <div><small>PHASE</small><strong>{{ phaseLabel }}</strong></div>
          <div><small>TIME</small><strong>{{ elapsedLabel }}</strong></div>
          <div><small>MISTAKES</small><strong>{{ mistakeCount }}</strong></div>
          <div><small>ACTIONS</small><strong>{{ completedActions }}/{{ totalActions }}</strong></div>
        </div>
      </header>

      <main v-if="!loading" class="lab-layout">
        <section class="chassis-card frost-panel">
          <div class="lab-topline">
            <div>
              <span class="eyebrow">SYSTEM UNIT PRACTICAL</span>
              <h2>Open-side chassis</h2>
              <p>Your actions are recorded. Work as you would on the actual unit.</p>
            </div>

            <button type="button" class="submit-button" :disabled="!canSubmit" @click="submitAttempt">
              {{ saving ? 'Submitting…' : submitted ? 'Submitted' : 'Submit Attempt' }}
            </button>
          </div>

          <div v-if="warning.visible" class="warning-card" role="alert" aria-live="assertive">
            <strong>{{ warning.title }}</strong>
            <span>{{ warning.message }}</span>
          </div>

          <div class="svg-wrap">
            <svg ref="chassisSvg" class="chassis-svg" viewBox="0 0 1000 650" xmlns="http://www.w3.org/2000/svg"
              role="group" aria-label="Interactive computer system unit chassis" @click="handleChassisClick">
              <defs>
                <linearGradient id="caseMetal" x1="0" x2="1" y1="0" y2="1">
                  <stop offset="0" stop-color="#F7F7F7" />
                  <stop offset=".2" stop-color="#C7C9CB" />
                  <stop offset=".48" stop-color="#F1F1F1" />
                  <stop offset=".75" stop-color="#9EA1A3" />
                  <stop offset="1" stop-color="#E3E4E5" />
                </linearGradient>
                <linearGradient id="darkMetal" x1="0" x2="0" y1="0" y2="1">
                  <stop offset="0" stop-color="#343638" />
                  <stop offset="1" stop-color="#151617" />
                </linearGradient>
                <linearGradient id="boardGreen" x1="0" x2="1" y1="0" y2="1">
                  <stop offset="0" stop-color="#397354" />
                  <stop offset=".5" stop-color="#235E43" />
                  <stop offset="1" stop-color="#173F2F" />
                </linearGradient>
                <linearGradient id="goldEdge" x1="0" x2="1">
                  <stop offset="0" stop-color="#986B25" />
                  <stop offset=".5" stop-color="#E2BE67" />
                  <stop offset="1" stop-color="#8B5B1C" />
                </linearGradient>
                <linearGradient id="driveMetal" x1="0" x2="1">
                  <stop offset="0" stop-color="#44484B" />
                  <stop offset=".45" stop-color="#8B9093" />
                  <stop offset="1" stop-color="#303335" />
                </linearGradient>
                <radialGradient id="fanHub">
                  <stop offset="0" stop-color="#C6C8C9" />
                  <stop offset=".42" stop-color="#5A5E61" />
                  <stop offset="1" stop-color="#181A1B" />
                </radialGradient>
                <pattern id="vent" width="14" height="14" patternUnits="userSpaceOnUse">
                  <circle cx="7" cy="7" r="2.2" fill="#666B6E" opacity=".55" />
                </pattern>
                <filter id="partShadow" x="-30%" y="-30%" width="170%" height="180%">
                  <feDropShadow dx="0" dy="7" stdDeviation="7" flood-color="#000" flood-opacity=".24" />
                </filter>
              </defs>

              <!-- Chassis shell -->
              <rect x="50" y="35" width="900" height="565" rx="27" fill="#000" opacity=".08" />
              <rect x="38" y="23" width="900" height="565" rx="27" fill="url(#darkMetal)" stroke="#111"
                stroke-width="4" />
              <rect x="62" y="49" width="852" height="515" rx="18" fill="url(#caseMetal)" stroke="#777C7F"
                stroke-width="2" />
              <rect x="85" y="73" width="800" height="467" rx="12" fill="#D6D8D9" stroke="#969A9D" stroke-width="1.5" />
              <rect x="95" y="84" width="35" height="438" rx="7" fill="url(#vent)" />
              <rect x="849" y="94" width="22" height="405" rx="7" fill="url(#vent)" />

              <!-- Bare standoffs remain when motherboard is removed -->
              <g fill="#8A8E91" stroke="#565A5D">
                <circle cx="260" cy="150" r="7" />
                <circle cx="600" cy="150" r="7" />
                <circle cx="260" cy="440" r="7" />
                <circle cx="600" cy="440" r="7" />
                <circle cx="430" cy="150" r="7" />
                <circle cx="430" cy="440" r="7" />
                <circle cx="600" cy="300" r="7" />
              </g>

              <!-- Motherboard -->
              <g v-if="isInstalled('motherboard')" class="interactive-part" :class="partMotionClass('motherboard')"
                data-key="motherboard" @pointerdown.stop="startSvgDrag('motherboard', $event)"
                @click.stop="selectPart('motherboard')">
                <rect x="215" y="120" width="430" height="355" rx="9" fill="url(#boardGreen)" stroke="#123827"
                  stroke-width="3" />
                <path d="M245 145 H590 M245 165 H470 M270 420 H605" stroke="#83A994" stroke-opacity=".35"
                  stroke-width="3" />
                <path d="M315 120 V475 M480 120 V475" stroke="#9BC2A8" stroke-opacity=".15" />
                <g fill="#B9C2BC" opacity=".85">
                  <circle cx="237" cy="142" r="5" />
                  <circle cx="620" cy="142" r="5" />
                  <circle cx="237" cy="450" r="5" />
                  <circle cx="620" cy="450" r="5" />
                  <circle cx="425" cy="142" r="5" />
                  <circle cx="425" cy="450" r="5" />
                  <circle cx="620" cy="300" r="5" />
                </g>
                <rect x="245" y="180" width="118" height="114" rx="8" fill="#244D38" stroke="#86A593" />
                <rect x="263" y="198" width="82" height="78" rx="4" fill="#B8BDBC" stroke="#777" />
                <rect x="516" y="156" width="12" height="162" rx="3" fill="#242728" />
                <rect x="538" y="156" width="12" height="162" rx="3" fill="#242728" />
                <rect x="560" y="156" width="12" height="162" rx="3" fill="#242728" />
                <rect x="582" y="156" width="12" height="162" rx="3" fill="#242728" />
                <rect x="270" y="342" width="320" height="13" rx="3" fill="#E5E5DD" />
                <rect x="270" y="371" width="285" height="11" rx="3" fill="#C5C5BE" />
                <rect x="270" y="399" width="250" height="11" rx="3" fill="#C5C5BE" />
                <circle cx="545" cy="407" r="28" fill="#C7C7BE" stroke="#6F6F69" />
                <circle cx="545" cy="407" r="19" fill="#A5A59F" />
                <rect x="603" y="180" width="18" height="92" rx="3" fill="#ECE8D2" />
                <rect x="593" y="310" width="28" height="42" rx="3" fill="#1A1B1A" />
                <g fill="#D4D8D1">
                  <rect x="238" y="310" width="42" height="18" rx="2" />
                  <rect x="289" y="310" width="42" height="18" rx="2" />
                  <rect x="340" y="310" width="42" height="18" rx="2" />
                </g>
              </g>

              <!-- RAM chips -->
              <g v-if="isInstalled('ram')" class="interactive-part" :class="partMotionClass('ram')"
                @pointerdown.stop="startSvgDrag('ram', $event)" @click.stop="selectPart('ram')">
                <rect x="521" y="162" width="8" height="148" rx="3" fill="#1B2331" stroke="#8492A2" />
                <rect x="543" y="162" width="8" height="148" rx="3" fill="#1B2331" stroke="#8492A2" />
                <g fill="#252B2F">
                  <rect x="520" y="180" width="10" height="18" rx="2" />
                  <rect x="520" y="212" width="10" height="18" rx="2" />
                  <rect x="520" y="244" width="10" height="18" rx="2" />
                  <rect x="542" y="180" width="10" height="18" rx="2" />
                  <rect x="542" y="212" width="10" height="18" rx="2" />
                  <rect x="542" y="244" width="10" height="18" rx="2" />
                </g>
                <rect x="520" y="303" width="10" height="5" fill="url(#goldEdge)" />
                <rect x="542" y="303" width="10" height="5" fill="url(#goldEdge)" />
              </g>

              <!-- CPU cooler -->
              <g v-if="isInstalled('cpu_fan')" class="interactive-part" :class="partMotionClass('cpu_fan')"
                @pointerdown.stop="startSvgDrag('cpu_fan', $event)" @click.stop="selectPart('cpu_fan')">
                <circle cx="304" cy="238" r="65" fill="#242728" stroke="#7A7F82" stroke-width="4" />
                <circle cx="304" cy="238" r="50" fill="none" stroke="#515558" stroke-width="7" />
                <g fill="#5F6467">
                  <path d="M304 202 C330 197 338 212 322 231 C317 220 309 211 304 202Z" />
                  <path d="M340 238 C345 264 330 272 311 256 C322 251 331 243 340 238Z" />
                  <path d="M304 274 C278 279 270 264 286 245 C291 256 299 265 304 274Z" />
                  <path d="M268 238 C263 212 278 204 297 220 C286 225 277 233 268 238Z" />
                </g>
                <circle cx="304" cy="238" r="14" fill="url(#fanHub)" />
              </g>

              <!-- CPU fan screws -->
              <g v-if="isInstalled('cpu_fan_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('cpu_fan_screws', $event)" @click.stop="selectPart('cpu_fan_screws')">
                <circle cx="252" cy="186" r="8" />
                <circle cx="356" cy="186" r="8" />
                <circle cx="252" cy="290" r="8" />
                <circle cx="356" cy="290" r="8" />
              </g>

              <!-- CPU fan wire -->
              <g v-if="isInstalled('cpu_fan_wire')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('cpu_fan_wire', $event)" @click.stop="selectPart('cpu_fan_wire')">
                <path d="M353 249 C402 245 416 212 454 211" fill="none" stroke="#202326" stroke-width="7" />
                <path d="M353 249 C402 245 416 212 454 211" fill="none" stroke="#E6C94C" stroke-width="2" />
                <rect x="448" y="202" width="20" height="17" rx="2" fill="#F2F2E8" stroke="#777" />
              </g>

              <!-- Expansion cards -->
              <g v-if="isInstalled('expansion_cards')" class="interactive-part"
                :class="partMotionClass('expansion_cards')" @pointerdown.stop="startSvgDrag('expansion_cards', $event)"
                @click.stop="selectPart('expansion_cards')">
                <rect x="245" y="345" width="330" height="62" rx="6" fill="#242729" stroke="#767B7E" stroke-width="2" />
                <circle cx="310" cy="376" r="22" fill="#121415" stroke="#5E6366" stroke-width="4" />
                <circle cx="425" cy="376" r="22" fill="#121415" stroke="#5E6366" stroke-width="4" />
                <rect x="255" y="405" width="270" height="8" fill="url(#goldEdge)" />
                <rect x="531" y="354" width="34" height="20" rx="3" fill="#2F3234" stroke="#74787A" />
              </g>

              <!-- Expansion card power -->
              <g v-if="isInstalled('gpu_power')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('gpu_power', $event)" @click.stop="selectPart('gpu_power')">
                <path d="M550 352 C618 345 650 374 692 414" fill="none" stroke="#1F2223" stroke-width="9" />
                <path d="M550 352 C618 345 650 374 692 414" fill="none" stroke="#D7BC44" stroke-width="2" />
              </g>

              <!-- Expansion slot covers -->
              <g v-if="isInstalled('expansion_covers')" class="interactive-part"
                @pointerdown.stop="startSvgDrag('expansion_covers', $event)"
                @click.stop="selectPart('expansion_covers')">
                <rect x="139" y="352" width="76" height="13" rx="3" fill="#AEB1B3" stroke="#717577" />
                <rect x="139" y="374" width="76" height="13" rx="3" fill="#AEB1B3" stroke="#717577" />
              </g>

              <!-- Expansion screws -->
              <g v-if="isInstalled('expansion_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('expansion_screws', $event)"
                @click.stop="selectPart('expansion_screws')">
                <circle cx="206" cy="358" r="7" />
                <circle cx="206" cy="380" r="7" />
              </g>

              <!-- PSU -->
              <g v-if="isInstalled('psu')" class="interactive-part" :class="partMotionClass('psu')"
                @pointerdown.stop="startSvgDrag('psu', $event)" @click.stop="selectPart('psu')">
                <rect x="105" y="430" width="250" height="100" rx="8" fill="url(#driveMetal)" stroke="#2B2E30"
                  stroke-width="3" />
                <circle cx="172" cy="480" r="35" fill="#161819" stroke="#6D7275" stroke-width="4" />
                <circle cx="172" cy="480" r="12" fill="url(#fanHub)" />
                <rect x="280" y="452" width="49" height="19" rx="3" fill="#17191A" />
                <rect x="290" y="486" width="28" height="17" rx="2" fill="#111" />
                <text x="255" y="516" fill="#D7D9DA" font-size="12" font-weight="700">POWER SUPPLY</text>
              </g>

              <!-- PSU screws -->
              <g v-if="isInstalled('psu_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('psu_screws', $event)" @click.stop="selectPart('psu_screws')">
                <circle cx="112" cy="438" r="7" />
                <circle cx="347" cy="438" r="7" />
                <circle cx="112" cy="521" r="7" />
                <circle cx="347" cy="521" r="7" />
              </g>

              <!-- ATX power -->
              <g v-if="isInstalled('atx_24pin')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('atx_24pin', $event)" @click.stop="selectPart('atx_24pin')">
                <path d="M342 444 C430 490 590 418 615 235" fill="none" stroke="#1C1E20" stroke-width="15" />
                <path d="M342 444 C430 490 590 418 615 235" fill="none" stroke="#D1B646" stroke-width="3" />
                <rect x="600" y="212" width="29" height="48" rx="3" fill="#EEEDE2" stroke="#676B6E" />
              </g>

              <!-- CPU power -->
              <g v-if="isInstalled('cpu_power')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('cpu_power', $event)" @click.stop="selectPart('cpu_power')">
                <path d="M324 443 C248 393 196 234 236 144" fill="none" stroke="#1B1D1F" stroke-width="11" />
                <path d="M324 443 C248 393 196 234 236 144" fill="none" stroke="#B83434" stroke-width="2" />
                <rect x="226" y="130" width="25" height="22" rx="3" fill="#EFEFE7" stroke="#666" />
              </g>

              <!-- Rear / large system fan -->
              <g v-if="isInstalled('system_fan')" class="interactive-part" :class="partMotionClass('system_fan')"
                @pointerdown.stop="startSvgDrag('system_fan', $event)" @click.stop="selectPart('system_fan')">
                <circle cx="157" cy="195" r="58" fill="#202325" stroke="#656A6D" stroke-width="4" />
                <circle cx="157" cy="195" r="45" fill="none" stroke="#414649" stroke-width="7" />
                <g fill="#595E61">
                  <path d="M157 160 C181 154 193 169 175 186 C171 175 164 166 157 160Z" />
                  <path d="M192 195 C198 219 183 231 166 213 C177 209 186 202 192 195Z" />
                  <path d="M157 230 C133 236 121 221 139 204 C143 215 150 224 157 230Z" />
                  <path d="M122 195 C116 171 131 159 148 177 C137 181 128 188 122 195Z" />
                </g>
                <circle cx="157" cy="195" r="12" fill="url(#fanHub)" />
              </g>

              <!-- Large fan screws -->
              <g v-if="isInstalled('system_fan_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('system_fan_screws', $event)"
                @click.stop="selectPart('system_fan_screws')">
                <circle cx="112" cy="150" r="7" />
                <circle cx="202" cy="150" r="7" />
                <circle cx="112" cy="240" r="7" />
                <circle cx="202" cy="240" r="7" />
              </g>

              <!-- Large fan wire -->
              <g v-if="isInstalled('system_fan_wire')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('system_fan_wire', $event)" @click.stop="selectPart('system_fan_wire')">
                <path d="M205 210 C237 218 254 304 286 316" fill="none" stroke="#222426" stroke-width="7" />
                <path d="M205 210 C237 218 254 304 286 316" fill="none" stroke="#BF3C3C" stroke-width="2" />
                <rect x="279" y="307" width="20" height="17" rx="2" fill="#EEEEEA" stroke="#6A6A68" />
              </g>

              <!-- Drive cage -->
              <rect x="678" y="110" width="177" height="350" rx="9" fill="#BFC2C4" stroke="#777B7E" stroke-width="2" />
              <path d="M696 125 V445 M837 125 V445" stroke="#8E9295" stroke-width="2" />
              <g fill="none" stroke="#95999C">
                <rect x="693" y="137" width="149" height="77" rx="5" />
                <rect x="693" y="228" width="149" height="59" rx="5" />
                <rect x="693" y="303" width="149" height="70" rx="5" />
                <rect x="693" y="389" width="149" height="46" rx="5" />
              </g>

              <!-- Optical drive -->
              <g v-if="isInstalled('optical_drive')" class="interactive-part" :class="partMotionClass('optical_drive')"
                @pointerdown.stop="startSvgDrag('optical_drive', $event)" @click.stop="selectPart('optical_drive')">
                <rect x="702" y="144" width="132" height="61" rx="5" fill="url(#driveMetal)" stroke="#484B4D" />
                <rect x="714" y="162" width="99" height="7" rx="3" fill="#252728" />
                <circle cx="820" cy="188" r="3" fill="#C4C6C7" />
              </g>

              <!-- Optical screws -->
              <g v-if="isInstalled('optical_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('optical_screws', $event)" @click.stop="selectPart('optical_screws')">
                <circle cx="695" cy="157" r="7" />
                <circle cx="840" cy="157" r="7" />
              </g>

              <!-- Card reader -->
              <g v-if="isInstalled('card_reader')" class="interactive-part" :class="partMotionClass('card_reader')"
                @pointerdown.stop="startSvgDrag('card_reader', $event)" @click.stop="selectPart('card_reader')">
                <rect x="704" y="236" width="128" height="43" rx="5" fill="#303335" stroke="#656A6D" />
                <rect x="718" y="249" width="44" height="7" rx="2" fill="#121415" />
                <rect x="773" y="249" width="42" height="7" rx="2" fill="#121415" />
                <rect x="718" y="263" width="95" height="5" rx="2" fill="#17191A" />
              </g>

              <!-- Card reader screws -->
              <g v-if="isInstalled('card_reader_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('card_reader_screws', $event)"
                @click.stop="selectPart('card_reader_screws')">
                <circle cx="696" cy="246" r="7" />
                <circle cx="840" cy="246" r="7" />
              </g>

              <!-- HDD -->
              <g v-if="isInstalled('hdd')" class="interactive-part" :class="partMotionClass('hdd')"
                @pointerdown.stop="startSvgDrag('hdd', $event)" @click.stop="selectPart('hdd')">
                <rect x="704" y="312" width="128" height="52" rx="5" fill="#45494B" stroke="#777C7F" />
                <circle cx="743" cy="338" r="17" fill="#B5B9BB" stroke="#5D6265" />
                <circle cx="743" cy="338" r="5" fill="#5D6265" />
                <path d="M760 338 L795 325" stroke="#D4D6D7" stroke-width="4" stroke-linecap="round" />
              </g>

              <!-- HDD screws -->
              <g v-if="isInstalled('hdd_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('hdd_screws', $event)" @click.stop="selectPart('hdd_screws')">
                <circle cx="697" cy="319" r="7" />
                <circle cx="839" cy="319" r="7" />
                <circle cx="697" cy="356" r="7" />
                <circle cx="839" cy="356" r="7" />
              </g>

              <!-- Portable HDD slot -->
              <g v-if="isInstalled('portable_hdd_slot')" class="interactive-part"
                @pointerdown.stop="startSvgDrag('portable_hdd_slot', $event)"
                @click.stop="selectPart('portable_hdd_slot')">
                <rect x="712" y="394" width="112" height="34" rx="5" fill="#696E71" stroke="#404346" />
                <rect x="729" y="401" width="78" height="19" rx="4" fill="#252729" />
              </g>

              <!-- Drive slot cover -->
              <g v-if="isInstalled('drive_slot_cover')" class="interactive-part"
                @pointerdown.stop="startSvgDrag('drive_slot_cover', $event)"
                @click.stop="selectPart('drive_slot_cover')">
                <rect x="854" y="140" width="22" height="75" rx="3" fill="#2D3032" stroke="#777" />
                <path d="M858 152 H872 M858 162 H872 M858 172 H872 M858 182 H872 M858 192 H872" stroke="#7E8285" />
              </g>

              <!-- Connectivity center -->
              <g v-if="isInstalled('connectivity_center')" class="interactive-part"
                @pointerdown.stop="startSvgDrag('connectivity_center', $event)"
                @click.stop="selectPart('connectivity_center')">
                <rect x="852" y="235" width="27" height="105" rx="5" fill="#333638" stroke="#74797C" />
                <rect x="858" y="248" width="15" height="10" rx="2" fill="#151718" />
                <rect x="858" y="266" width="15" height="10" rx="2" fill="#151718" />
                <circle cx="865" cy="294" r="4" fill="#8E9295" />
                <circle cx="865" cy="311" r="4" fill="#8E9295" />
              </g>

              <!-- Connectivity center screws -->
              <g v-if="isInstalled('io_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('io_screws', $event)" @click.stop="selectPart('io_screws')">
                <circle cx="849" cy="244" r="6" />
                <circle cx="881" cy="331" r="6" />
              </g>

              <!-- LED plastic piece -->
              <g v-if="isInstalled('plastic_led_piece')" class="interactive-part"
                @pointerdown.stop="startSvgDrag('plastic_led_piece', $event)"
                @click.stop="selectPart('plastic_led_piece')">
                <rect x="851" y="365" width="29" height="61" rx="7" fill="#D5D7D8" fill-opacity=".65"
                  stroke="#8D9194" />
                <circle cx="865" cy="383" r="6" fill="#8F9396" />
                <circle cx="865" cy="404" r="3" fill="#3A6B4C" />
              </g>

              <!-- Motherboard screws -->
              <g v-if="isInstalled('motherboard_screws')" class="interactive-part screw-group"
                @pointerdown.stop="startSvgDrag('motherboard_screws', $event)"
                @click.stop="selectPart('motherboard_screws')">
                <circle cx="237" cy="142" r="7" />
                <circle cx="620" cy="142" r="7" />
                <circle cx="237" cy="450" r="7" />
                <circle cx="620" cy="450" r="7" />
                <circle cx="425" cy="142" r="7" />
                <circle cx="425" cy="450" r="7" />
                <circle cx="620" cy="300" r="7" />
              </g>

              <!-- SATA cable -->
              <g v-if="isInstalled('sata_cable_hdd')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('sata_cable_hdd', $event)" @click.stop="selectPart('sata_cable_hdd')">
                <path d="M694 346 C630 350 610 374 598 393" fill="none" stroke="#BE2F2F" stroke-width="8" />
                <rect x="586" y="384" width="23" height="17" rx="2" fill="#161819" />
              </g>

              <!-- Portable HDD wires -->
              <g v-if="isInstalled('portable_hdd_wires')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('portable_hdd_wires', $event)"
                @click.stop="selectPart('portable_hdd_wires')">
                <path d="M710 411 C653 432 628 423 602 407" fill="none" stroke="#242729" stroke-width="8" />
                <path d="M710 421 C664 449 640 449 610 431" fill="none" stroke="#D5B943" stroke-width="5" />
              </g>

              <!-- Optical ribbon -->
              <g v-if="isInstalled('optical_ribbon')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('optical_ribbon', $event)" @click.stop="selectPart('optical_ribbon')">
                <path d="M702 184 C652 183 626 205 593 294" fill="none" stroke="#777B7D" stroke-width="18" />
                <path d="M702 184 C652 183 626 205 593 294" fill="none" stroke="#AEB1B3" stroke-width="11" />
              </g>

              <!-- Card reader wire -->
              <g v-if="isInstalled('card_reader_wire')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('card_reader_wire', $event)"
                @click.stop="selectPart('card_reader_wire')">
                <path d="M703 257 C653 270 642 318 600 333" fill="none" stroke="#282A2C" stroke-width="7" />
                <path d="M703 257 C653 270 642 318 600 333" fill="none" stroke="#4D74A6" stroke-width="2" />
              </g>

              <!-- Front panel wire -->
              <g v-if="isInstalled('front_panel_wire')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('front_panel_wire', $event)"
                @click.stop="selectPart('front_panel_wire')">
                <path d="M850 392 C730 456 662 452 605 432" fill="none" stroke="#222426" stroke-width="8" />
                <path d="M850 392 C730 456 662 452 605 432" fill="none" stroke="#D7D7D1" stroke-width="2" />
              </g>

              <!-- Drive power -->
              <g v-if="isInstalled('drive_power')" class="interactive-part cable-part"
                @pointerdown.stop="startSvgDrag('drive_power', $event)" @click.stop="selectPart('drive_power')">
                <path d="M330 458 C515 533 664 503 707 357" fill="none" stroke="#202224" stroke-width="12" />
                <path d="M330 458 C515 533 664 503 707 357" fill="none" stroke="#D0AF39" stroke-width="3" />
              </g>

              <!-- Side panel under its screws but above internals -->
              <g v-if="isInstalled('side_panel')" class="interactive-part side-panel-part"
                :class="partMotionClass('side_panel')" @pointerdown.stop="startSvgDrag('side_panel', $event)"
                @click.stop="selectPart('side_panel')">
                <rect x="72" y="64" width="810" height="485" rx="16" fill="#D8DBDC" fill-opacity=".82" stroke="#8B9093"
                  stroke-width="3" />
                <rect x="124" y="112" width="695" height="379" rx="11" fill="#FFFFFF" fill-opacity=".09"
                  stroke="#A9ADAF" />
                <path d="M102 92 H853 M102 523 H853" stroke="#F8F8F8" stroke-opacity=".55" stroke-width="3" />
                <text x="478" y="302" text-anchor="middle" fill="#606467" font-size="17" font-weight="700"
                  letter-spacing="3">SYSTEM UNIT SIDE PANEL</text>
              </g>

              <!-- Side panel screws -->
              <g v-if="isInstalled('side_panel_screws')" class="interactive-part screw-group side-screw-group"
                @pointerdown.stop="startSvgDrag('side_panel_screws', $event)"
                @click.stop="selectPart('side_panel_screws')">
                <circle cx="857" cy="98" r="10" />
                <circle cx="857" cy="513" r="10" />
              </g>

              <!-- AC cable outside chassis -->
              <g v-if="isInstalled('ac_power_cable')" class="interactive-part cable-part"
                :class="partMotionClass('ac_power_cable')" @pointerdown.stop="startSvgDrag('ac_power_cable', $event)"
                @click.stop="selectPart('ac_power_cable')">
                <path d="M105 486 C45 498 26 544 61 605 C90 632 131 623 147 594" fill="none" stroke="#202224"
                  stroke-width="15" stroke-linecap="round" />
                <rect x="84" y="475" width="28" height="24" rx="4" fill="#111314" stroke="#676B6E" />
                <rect x="135" y="580" width="32" height="31" rx="5" fill="#1C1E20" stroke="#656A6D" />
                <rect x="141" y="608" width="5" height="15" fill="#B9BBBC" />
                <rect x="156" y="608" width="5" height="15" fill="#B9BBBC" />
              </g>
            </svg>
          </div>
        </section>

        <aside class="bench-column">
          <section ref="partsTrayRef" class="tray-panel frost-panel"
            :class="{ selected: selectedPart && itemType(selectedPart) !== 'cable' && itemType(selectedPart) !== 'fastener' }"
            @click="handleTrayClick('parts_tray')">
            <div class="tray-heading"><span>REMOVED HARDWARE</span><strong>{{ hardwareTray.length }}</strong></div>
            <div class="tray-list">
              <button v-for="item in hardwareTray" :key="item.component_key" type="button" class="tray-item"
                :class="{ chosen: selectedPart === item.component_key }"
                @pointerdown.stop="startTrayDrag(item.component_key, $event)"
                @click.stop="selectPart(item.component_key)">
                <img v-if="item.image" :src="item.image" :alt="item.display_name" />
                <span>{{ item.display_name }}</span><small v-if="item.quantity > 1">×{{ item.quantity }}</small>
              </button>
              <p v-if="!hardwareTray.length" class="tray-empty">Empty</p>
            </div>
          </section>

          <section ref="cableTrayRef" class="tray-panel frost-panel" @click="handleTrayClick('cable_tray')">
            <div class="tray-heading"><span>DISCONNECTED CABLES</span><strong>{{ cableTray.length }}</strong></div>
            <div class="tray-list compact">
              <button v-for="item in cableTray" :key="item.component_key" type="button" class="tray-item cable-card"
                :class="{ chosen: selectedPart === item.component_key }"
                @pointerdown.stop="startTrayDrag(item.component_key, $event)"
                @click.stop="selectPart(item.component_key)">
                <span>{{ item.display_name }}</span><small v-if="item.quantity > 1">×{{ item.quantity }}</small>
              </button>
              <p v-if="!cableTray.length" class="tray-empty">Empty</p>
            </div>
          </section>

          <section ref="screwTrayRef" class="tray-panel frost-panel" @click="handleTrayClick('screw_tray')">
            <div class="tray-heading"><span>SCREWS / FASTENERS</span><strong>{{ screwQuantity }}</strong></div>
            <div class="tray-list compact">
              <button v-for="item in screwTray" :key="item.component_key" type="button" class="tray-item screw-card"
                :class="{ chosen: selectedPart === item.component_key }"
                @pointerdown.stop="startTrayDrag(item.component_key, $event)"
                @click.stop="selectPart(item.component_key)">
                <span class="screw-icon">✣</span><span>{{ item.display_name }}</span><small>×{{ item.quantity }}</small>
              </button>
              <p v-if="!screwTray.length" class="tray-empty">Empty</p>
            </div>
          </section>

          <section class="attempt-note frost-panel">
            <strong>Assessment mode</strong>
            <p>No procedure hints are shown. Incorrect actions are recorded and the unit remains unchanged until the
              action is valid.</p>
          </section>
        </aside>
      </main>

      <div v-else class="loading-card frost-panel">Loading practical assessment…</div>

      <div v-if="dragState" class="drag-preview" :style="{ left: `${dragState.x}px`, top: `${dragState.y}px` }"
        aria-hidden="true">
        <strong>{{ displayName(dragState.key) }}</strong>
        <small>{{ itemType(dragState.key) }}</small>
      </div>

      <div v-if="submitted && result" class="result-overlay">
        <div class="result-card frost-panel">
          <span class="eyebrow">ATTEMPT RECORDED</span>
          <h2>{{ result.passed ? 'Passed' : 'Recorded as failed' }}</h2>
          <strong class="result-score">{{ Number(result.percentage || 0).toFixed(2) }}%</strong>
          <p>{{ result.correct_actions }} first-try actions · {{ result.mistake_count }} mistakes</p>
          <button type="button" class="submit-button" @click="goBack">Back to assessments</button>
        </div>
      </div>

      <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { hardwareProcedureRequest } from '@/assessmentrequests/hardwareProcedure'

const route = useRoute()
const router = useRouter()
const emit = defineEmits(['complete'])

const quiz = ref({})
const assessment = ref({})
const question = ref({})
const catalog = ref([])
const attemptId = ref(null)
const phase = ref('disassemble')
const loading = ref(true)
const saving = ref(false)
const submitted = ref(false)
const result = ref(null)
const completedActions = ref(0)
const totalActions = ref(0)
const mistakeCount = ref(0)
const actionCount = ref(0)
const selectedPart = ref(null)
const dragState = ref(null)
const movingKey = ref(null)
const movingDirection = ref(null)
const announcement = ref('')
const warning = ref({ visible: false, title: '', message: '' })
const startedAt = ref(Date.now())
const now = ref(Date.now())
const chassisSvg = ref(null)
const partsTrayRef = ref(null)
const cableTrayRef = ref(null)
const screwTrayRef = ref(null)
const installedState = ref({})
let timer = null
let warningTimer = null
let ignoreClickUntil = 0

const quizId = computed(() => route.params.id)
const catalogByKey = computed(() => Object.fromEntries(catalog.value.map(item => [item.component_key, item])))

const phaseLabel = computed(() => {
  if (phase.value === 'assemble') return 'Assembly'
  if (phase.value === 'complete') return 'Complete'
  return 'Disassembly'
})

const elapsedLabel = computed(() => {
  const seconds = Math.max(0, Math.floor((now.value - startedAt.value) / 1000))
  const minutes = Math.floor(seconds / 60)
  const remain = seconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remain).padStart(2, '0')}`
})

const canSubmit = computed(() => Boolean(attemptId.value) && actionCount.value > 0 && !saving.value && !submitted.value)
const hardwareTray = computed(() => catalog.value.filter(item => !isInstalled(item.component_key) && !['cable', 'fastener'].includes(item.item_type)))
const cableTray = computed(() => catalog.value.filter(item => !isInstalled(item.component_key) && item.item_type === 'cable'))
const screwTray = computed(() => catalog.value.filter(item => !isInstalled(item.component_key) && item.item_type === 'fastener'))
const screwQuantity = computed(() => screwTray.value.reduce((sum, item) => sum + Number(item.quantity || 1), 0))

const installedZoneByKey = {
  ac_power_cable: 'ac_inlet',
  side_panel_screws: 'side_screws',
  side_panel: 'side_panel',
  front_panel_wire: 'front_header',
  card_reader_wire: 'reader_header',
  sata_cable_hdd: 'sata_port',
  portable_hdd_wires: 'dock_ports',
  optical_ribbon: 'optical_header',
  atx_24pin: 'atx_24pin',
  cpu_power: 'cpu_power',
  drive_power: 'drive_power',
  gpu_power: 'gpu_power',
  drive_slot_cover: 'drive_cover',
  expansion_screws: 'exp_screws',
  expansion_cards: 'pcie_slots',
  expansion_covers: 'exp_covers',
  ram: 'ram_slots',
  cpu_fan_wire: 'cpu_fan_header',
  cpu_fan_screws: 'cpu_fan_screws',
  cpu_fan: 'cpu_fan_mount',
  system_fan_wire: 'case_fan_header',
  system_fan_screws: 'case_fan_screws',
  system_fan: 'case_fan_mount',
  card_reader_screws: 'reader_screws',
  card_reader: 'reader_bay',
  optical_screws: 'optical_screws',
  optical_drive: 'optical_bay',
  hdd_screws: 'hdd_screws',
  hdd: 'hdd_bay',
  portable_hdd_slot: 'dock_bay',
  plastic_led_piece: 'led_mount',
  io_screws: 'io_screws',
  connectivity_center: 'io_center',
  psu_screws: 'psu_screws',
  psu: 'psu_bay',
  motherboard_screws: 'mb_screws',
  motherboard: 'mb_mount',
}

const zoneRects = [
  ['ac_inlet', { x: 70, y: 455, w: 75, h: 80 }],
  ['side_screws', { x: 825, y: 75, w: 65, h: 465 }],
  ['side_panel', { x: 65, y: 60, w: 825, h: 500 }],
  ['front_header', { x: 565, y: 410, w: 85, h: 58 }],
  ['reader_header', { x: 570, y: 305, w: 78, h: 55 }],
  ['sata_port', { x: 565, y: 370, w: 85, h: 55 }],
  ['dock_ports', { x: 680, y: 385, w: 170, h: 60 }],
  ['optical_header', { x: 560, y: 270, w: 95, h: 60 }],
  ['atx_24pin', { x: 585, y: 190, w: 65, h: 95 }],
  ['cpu_power', { x: 215, y: 115, w: 70, h: 65 }],
  ['drive_power', { x: 660, y: 300, w: 195, h: 95 }],
  ['gpu_power', { x: 520, y: 330, w: 120, h: 75 }],
  ['drive_cover', { x: 838, y: 125, w: 50, h: 105 }],
  ['exp_screws', { x: 180, y: 335, w: 55, h: 70 }],
  ['pcie_slots', { x: 235, y: 325, w: 360, h: 105 }],
  ['exp_covers', { x: 120, y: 330, w: 100, h: 85 }],
  ['ram_slots', { x: 495, y: 145, w: 115, h: 185 }],
  ['cpu_fan_header', { x: 420, y: 185, w: 70, h: 65 }],
  ['cpu_fan_screws', { x: 235, y: 170, w: 145, h: 140 }],
  ['cpu_fan_mount', { x: 235, y: 165, w: 145, h: 145 }],
  ['case_fan_header', { x: 250, y: 285, w: 80, h: 65 }],
  ['case_fan_screws', { x: 90, y: 130, w: 135, h: 135 }],
  ['case_fan_mount', { x: 90, y: 130, w: 135, h: 135 }],
  ['reader_screws', { x: 675, y: 225, w: 180, h: 75 }],
  ['reader_bay', { x: 690, y: 220, w: 160, h: 80 }],
  ['optical_screws', { x: 675, y: 125, w: 180, h: 100 }],
  ['optical_bay', { x: 690, y: 125, w: 160, h: 100 }],
  ['hdd_screws', { x: 675, y: 295, w: 180, h: 90 }],
  ['hdd_bay', { x: 690, y: 295, w: 160, h: 90 }],
  ['dock_bay', { x: 690, y: 380, w: 160, h: 65 }],
  ['led_mount', { x: 835, y: 345, w: 55, h: 95 }],
  ['io_screws', { x: 830, y: 215, w: 60, h: 145 }],
  ['io_center', { x: 830, y: 220, w: 60, h: 135 }],
  ['psu_screws', { x: 90, y: 415, w: 285, h: 135 }],
  ['psu_bay', { x: 90, y: 415, w: 285, h: 135 }],
  ['mb_screws', { x: 200, y: 105, w: 465, h: 390 }],
  ['mb_mount', { x: 200, y: 105, w: 465, h: 390 }],
]

function itemType(key) {
  return catalogByKey.value[key]?.item_type || 'hardware'
}

function displayName(key) {
  return catalogByKey.value[key]?.display_name || key
}

function isInstalled(key) {
  return installedState.value[key] !== false
}

function trayForKey(key) {
  const type = itemType(key)
  if (type === 'cable') return 'cable_tray'
  if (type === 'fastener') return 'screw_tray'
  return 'parts_tray'
}

function sourceZoneForKey(key) {
  return isInstalled(key) ? (installedZoneByKey[key] || 'chassis') : trayForKey(key)
}

function inferAction(key, destinationZone) {
  const type = itemType(key)
  const fromInstalled = isInstalled(key)
  const goingToTray = ['parts_tray', 'cable_tray', 'screw_tray'].includes(destinationZone)

  if (fromInstalled && goingToTray) {
    if (type === 'fastener') return 'unscrew'
    if (type === 'cable') return key === 'ac_power_cable' ? 'unplug' : 'disconnect'
    return 'remove'
  }

  if (!fromInstalled && !goingToTray) {
    if (type === 'fastener') return 'screw'
    if (type === 'cable') return key === 'ac_power_cable' ? 'plug' : 'connect'
    return 'install'
  }

  if (fromInstalled) {
    if (type === 'fastener') return 'unscrew'
    if (type === 'cable') return key === 'ac_power_cable' ? 'unplug' : 'disconnect'
    return 'remove'
  }

  if (type === 'fastener') return 'screw'
  if (type === 'cable') return key === 'ac_power_cable' ? 'plug' : 'connect'
  return 'install'
}

function partMotionClass(key) {
  return {
    'moving-out': movingKey.value === key && movingDirection.value === 'out',
    'moving-in': movingKey.value === key && movingDirection.value === 'in',
    selected: selectedPart.value === key,
  }
}

async function load() {
  loading.value = true

  try {
    const response = await hardwareProcedureRequest.get(quizId.value)
    if (!response?.success || !response?.data) throw new Error('Invalid hardware API response.')

    quiz.value = response.data.quiz || {}
    assessment.value = response.data.assessment || {}
    question.value = response.data.question || {}
    catalog.value = Array.isArray(response.data.components) ? response.data.components : []
    totalActions.value = Number(response.data.total_actions || 0)

    installedState.value = Object.fromEntries(catalog.value.map(item => [item.component_key, true]))

    const started = await hardwareProcedureRequest.start(quizId.value)
    if (!started?.success || !started?.data) throw new Error('Unable to start the practical attempt.')

    attemptId.value = started.data.attempt_id
    phase.value = started.data.phase || 'disassemble'
    completedActions.value = Number(started.data.completed_actions || 0)
    totalActions.value = Number(started.data.total_actions || totalActions.value)

    const history = Array.isArray(started.data.history) ? started.data.history : []
    actionCount.value = history.length
    history.filter(row => row.is_correct).forEach(applyHistoryAction)
    startedAt.value = Date.now()
  } catch (error) {
    console.error('Failed to load hardware practical:', error)
    showWarning('Unable to load assessment', error?.response?.data?.message || error?.message || 'Please try again.')
  } finally {
    loading.value = false
  }
}

function applyHistoryAction(row) {
  const outActions = ['unplug', 'disconnect', 'unscrew', 'remove']
  const inActions = ['plug', 'connect', 'screw', 'install']

  if (outActions.includes(row.performed_action)) {
    installedState.value = { ...installedState.value, [row.component_key]: false }
  } else if (inActions.includes(row.performed_action)) {
    installedState.value = { ...installedState.value, [row.component_key]: true }
  }
}

function selectPart(key) {
  if (submitted.value || saving.value || Date.now() < ignoreClickUntil) return
  selectedPart.value = selectedPart.value === key ? null : key
  announcement.value = selectedPart.value ? `${displayName(key)} selected.` : 'Selection cleared.'
}

function startSvgDrag(key, event) {
  beginDrag(key, event)
}

function startTrayDrag(key, event) {
  beginDrag(key, event)
}

function beginDrag(key, event) {
  if (submitted.value || saving.value) return
  event.preventDefault()
  selectedPart.value = key
  dragState.value = {
    key,
    startX: event.clientX,
    startY: event.clientY,
    x: event.clientX + 14,
    y: event.clientY + 14,
    moved: false,
  }
  window.addEventListener('pointermove', onPointerMove, { passive: false })
  window.addEventListener('pointerup', onPointerUp)
  window.addEventListener('pointercancel', cancelDrag)
}

function onPointerMove(event) {
  if (!dragState.value) return
  event.preventDefault()
  const dx = event.clientX - dragState.value.startX
  const dy = event.clientY - dragState.value.startY
  dragState.value = {
    ...dragState.value,
    x: event.clientX + 14,
    y: event.clientY + 14,
    moved: dragState.value.moved || Math.hypot(dx, dy) > 6,
  }
}

async function onPointerUp(event) {
  const state = dragState.value
  if (!state) return

  if (state.moved) {
    const destination = destinationFromPointer(event.clientX, event.clientY)
    if (destination) {
      await attemptAction(state.key, destination)
    }
    ignoreClickUntil = Date.now() + 250
  }

  cancelDrag()
}

function cancelDrag() {
  dragState.value = null
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
  window.removeEventListener('pointercancel', cancelDrag)
}

function destinationFromPointer(clientX, clientY) {
  const trays = [
    ['parts_tray', partsTrayRef.value],
    ['cable_tray', cableTrayRef.value],
    ['screw_tray', screwTrayRef.value],
  ]

  for (const [zone, element] of trays) {
    const rect = element?.getBoundingClientRect?.()
    if (rect && pointInRect(clientX, clientY, rect.left, rect.top, rect.width, rect.height)) return zone
  }

  const svg = chassisSvg.value
  const rect = svg?.getBoundingClientRect?.()
  if (!svg || !rect || rect.width <= 0 || rect.height <= 0) return null
  if (!pointInRect(clientX, clientY, rect.left, rect.top, rect.width, rect.height)) return null

  const x = ((clientX - rect.left) / rect.width) * 1000
  const y = ((clientY - rect.top) / rect.height) * 650

  return zoneFromSvgPoint(x, y)
}

function zoneFromSvgPoint(x, y) {
  const preferred = selectedPart.value ? installedZoneByKey[selectedPart.value] : null
  if (preferred) {
    const preferredZone = zoneRects.find(([name]) => name === preferred)?.[1]
    if (preferredZone && pointInZone(x, y, preferredZone)) return preferred
  }

  for (const [name, zone] of zoneRects) {
    if (name === 'side_panel' || name === 'mb_mount') continue
    if (pointInZone(x, y, zone)) return name
  }

  const sidePanelZone = zoneRects.find(([name]) => name === 'side_panel')?.[1]
  if (sidePanelZone && pointInZone(x, y, sidePanelZone)) return 'side_panel'

  const motherboardZone = zoneRects.find(([name]) => name === 'mb_mount')?.[1]
  if (motherboardZone && pointInZone(x, y, motherboardZone)) return 'mb_mount'

  return 'chassis'
}

function pointInZone(x, y, zone) {
  return x >= zone.x && x <= zone.x + zone.w && y >= zone.y && y <= zone.y + zone.h
}

function pointInRect(x, y, left, top, width, height) {
  return x >= left && x <= left + width && y >= top && y <= top + height
}

async function handleChassisClick(event) {
  if (!selectedPart.value || Date.now() < ignoreClickUntil) return
  const destination = destinationFromPointer(event.clientX, event.clientY)
  if (destination && !destination.endsWith('_tray')) await attemptAction(selectedPart.value, destination)
}

async function handleTrayClick(zone) {
  if (!selectedPart.value || Date.now() < ignoreClickUntil) return
  await attemptAction(selectedPart.value, zone)
}

async function attemptAction(key, destinationZone) {
  if (!attemptId.value || saving.value || submitted.value) return

  const sourceZone = sourceZoneForKey(key)
  if (sourceZone === destinationZone) return

  const performedAction = inferAction(key, destinationZone)
  saving.value = true
  clearWarning()

  try {
    const response = await hardwareProcedureRequest.action(quizId.value, attemptId.value, {
      component_key: key,
      performed_action: performedAction,
      source_zone: sourceZone,
      destination_zone: destinationZone,
    })

    if (!response?.success || !response?.data) throw new Error('Invalid action response.')

    actionCount.value += 1
    completedActions.value = Number(response.data.completed_actions || 0)
    totalActions.value = Number(response.data.total_actions || totalActions.value)
    mistakeCount.value = Number(response.data.mistake_count || 0)

    const oldPhase = phase.value
    phase.value = response.data.phase || phase.value

    if (!response.data.allowed) {
      showWarning(
        'Action not allowed yet',
        'That action was recorded, but the unit was not changed. Check cables, screws, covers, and component dependencies before trying again.',
      )
      announcement.value = 'Incorrect action recorded.'
      return
    }

    const movingOut = ['unplug', 'disconnect', 'unscrew', 'remove'].includes(performedAction)
    movingKey.value = key
    movingDirection.value = movingOut ? 'out' : 'in'

    if (!movingOut) {
      installedState.value = { ...installedState.value, [key]: true }
    }

    await sleep(220)

    installedState.value = { ...installedState.value, [key]: !movingOut }
    movingKey.value = null
    movingDirection.value = null
    selectedPart.value = null

    if (oldPhase !== phase.value && phase.value === 'assemble') {
      showWarning('Assembly phase', 'Disassembly has been recorded. Reassemble the system unit.', false)
    } else if (phase.value === 'complete') {
      showWarning('Practical sequence complete', 'Submit the attempt to record the final result.', false)
    }

    announcement.value = 'Action accepted.'
  } catch (error) {
    console.error('Hardware action failed:', error)
    showWarning('Unable to record action', error?.response?.data?.message || error?.message || 'Try again.')
  } finally {
    saving.value = false
  }
}

async function submitAttempt() {
  if (!canSubmit.value) return
  saving.value = true

  try {
    const response = await hardwareProcedureRequest.complete(quizId.value, attemptId.value)
    if (!response?.success || !response?.data) throw new Error('Invalid submission response.')

    result.value = response.data
    submitted.value = true
    mistakeCount.value = mistakeCount.value
    completedActions.value = Number(response.data.completed_actions || completedActions.value)
    emit('complete', response.data)
  } catch (error) {
    console.error('Failed to submit attempt:', error)
    showWarning('Submission failed', error?.response?.data?.message || error?.message || 'Try again.')
  } finally {
    saving.value = false
  }
}

function showWarning(title, message, autoHide = true) {
  clearTimeout(warningTimer)
  warning.value = { visible: true, title, message }
  announcement.value = `${title}. ${message}`
  if (autoHide) {
    warningTimer = setTimeout(clearWarning, 4800)
  }
}

function clearWarning() {
  clearTimeout(warningTimer)
  warning.value = { visible: false, title: '', message: '' }
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

function goBack() {
  router.push(`/user/quizzes/assessment/${encodeURIComponent(String(quizId.value))}`)
}

onMounted(() => {
  load()
  timer = window.setInterval(() => { now.value = Date.now() }, 1000)
})

onBeforeUnmount(() => {
  cancelDrag()
  clearTimeout(warningTimer)
  if (timer) window.clearInterval(timer)
})
</script>

<style scoped>
.hardware-page {
  --black: #000000;
  --white: #ffffff;
  --silver: #a9a9a9;
  --light: #d3d3d3;
  --dim: #696969;
  --green: #1b7f45;
  --red: #bf2f38;
  min-height: 100vh;
  color: var(--black);
  background:
    radial-gradient(circle at 10% 3%, rgba(211, 211, 211, .25), transparent 34%),
    radial-gradient(circle at 95% 80%, rgba(211, 211, 211, .18), transparent 42%),
    #f7f7f6;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.hardware-page *,
.hardware-page *::before,
.hardware-page *::after {
  box-sizing: border-box;
}

button {
  font: inherit;
}

.page-shell {
  width: min(1500px, 100%);
  margin: auto;
  padding: 24px;
}

.frost-panel {
  border: 1px solid rgba(169, 169, 169, .38);
  background: rgba(255, 255, 255, .88);
  box-shadow: 0 16px 38px rgba(0, 0, 0, .055);
  backdrop-filter: blur(16px);
}

.page-header {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 18px;
}

.back-button {
  width: 43px;
  height: 43px;
  flex: 0 0 43px;
  border: 1px solid var(--light);
  border-radius: 11px;
  background: var(--white);
  cursor: pointer;
}

.heading-copy {
  min-width: 0;
  flex: 1;
}

.project-line {
  margin: 0 0 4px;
  display: flex;
  gap: 8px;
  color: var(--dim);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.heading-copy h1 {
  margin: 0;
  font-size: clamp(22px, 2.5vw, 31px);
  letter-spacing: -.04em;
}

.heading-copy>p:last-child {
  margin: 4px 0 0;
  color: var(--dim);
  font-size: 12px;
}

.performance-strip {
  display: grid;
  grid-template-columns: repeat(4, auto);
  gap: 9px;
}

.performance-strip>div {
  min-width: 84px;
  padding: 8px 10px;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: rgba(255, 255, 255, .85);
}

.performance-strip small {
  display: block;
  margin-bottom: 2px;
  color: var(--silver);
  font-size: 7px;
  font-weight: 800;
  letter-spacing: .1em;
}

.performance-strip strong {
  font-size: 11px;
}

.lab-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 310px;
  gap: 14px;
  align-items: start;
}

.chassis-card {
  min-width: 0;
  padding: 18px;
  border-radius: 18px;
}

.lab-topline {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: flex-start;
  margin-bottom: 13px;
}

.eyebrow {
  display: block;
  margin-bottom: 5px;
  color: var(--dim);
  font-size: 8px;
  font-weight: 800;
  letter-spacing: .12em;
}

.lab-topline h2 {
  margin: 0;
  font-size: 20px;
}

.lab-topline p {
  margin: 5px 0 0;
  color: var(--dim);
  font-size: 11px;
}

.submit-button {
  min-height: 39px;
  padding: 9px 14px;
  border: 1px solid var(--black);
  border-radius: 9px;
  background: var(--black);
  color: var(--white);
  font-weight: 700;
  font-size: 10px;
  cursor: pointer;
}

.submit-button:disabled {
  opacity: .38;
  cursor: not-allowed;
}

.warning-card {
  display: flex;
  flex-wrap: wrap;
  gap: 5px 10px;
  padding: 10px 12px;
  margin-bottom: 10px;
  border: 1px solid rgba(191, 47, 56, .38);
  border-radius: 10px;
  background: rgba(191, 47, 56, .05);
  color: #74232a;
  font-size: 10px;
}

.warning-card strong {
  color: var(--red);
}

.svg-wrap {
  overflow: hidden;
  border: 1px solid #d2d4d5;
  border-radius: 15px;
  background: linear-gradient(145deg, #fdfdfd, #e9ebec);
}

.chassis-svg {
  display: block;
  width: 100%;
  min-height: 520px;
  max-height: 720px;
  touch-action: none;
  user-select: none;
}

.interactive-part {
  cursor: grab;
  transition: opacity .22s ease, filter .18s ease, transform .22s ease;
  transform-box: fill-box;
  transform-origin: center;
}

.interactive-part:hover {
  filter: drop-shadow(0 0 8px rgba(0, 0, 0, .28));
}

.interactive-part.selected {
  filter: drop-shadow(0 0 9px rgba(0, 0, 0, .4));
}

.interactive-part:active {
  cursor: grabbing;
}

.interactive-part.moving-out {
  opacity: 0;
  transform: translate(110px, 55px) scale(.84);
}

.interactive-part.moving-in {
  animation: installPart .22s ease-out;
}

@keyframes installPart {
  from {
    opacity: .25;
    transform: translate(95px, 55px) scale(.82);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

.screw-group {
  fill: #8f9395;
  stroke: #46494b;
  stroke-width: 2;
}

.screw-group circle::after {
  content: '';
}

.cable-part {
  cursor: grab;
}

.side-panel-part {
  cursor: grab;
}

.bench-column {
  display: grid;
  gap: 10px;
}

.tray-panel {
  min-height: 125px;
  padding: 12px;
  border-radius: 14px;
  cursor: default;
}

.tray-panel:hover {
  border-color: var(--silver);
}

.tray-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
  color: var(--dim);
  font-size: 8px;
  font-weight: 800;
  letter-spacing: .08em;
}

.tray-heading strong {
  min-width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border: 1px solid var(--light);
  border-radius: 7px;
  color: var(--black);
  background: var(--white);
}

.tray-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px;
}

.tray-list.compact {
  grid-template-columns: 1fr;
}

.tray-item {
  min-width: 0;
  min-height: 58px;
  padding: 7px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  border: 1px solid #e0e0e0;
  border-radius: 9px;
  background: #fafafa;
  color: #333;
  cursor: grab;
  text-align: center;
  font-size: 9px;
}

.tray-item:hover,
.tray-item.chosen {
  border-color: var(--black);
  background: var(--white);
}

.tray-item img {
  width: 42px;
  height: 42px;
  object-fit: contain;
  pointer-events: none;
}

.tray-item small {
  color: var(--dim);
}

.cable-card,
.screw-card {
  min-height: 43px;
  flex-direction: row;
  justify-content: flex-start;
  text-align: left;
}

.screw-icon {
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border: 1px solid var(--light);
  border-radius: 50%;
}

.tray-empty {
  grid-column: 1/-1;
  margin: 18px 0;
  color: var(--silver);
  text-align: center;
  font-size: 10px;
}

.attempt-note {
  padding: 12px;
  border-radius: 14px;
}

.attempt-note strong {
  font-size: 10px;
}

.attempt-note p {
  margin: 5px 0 0;
  color: var(--dim);
  font-size: 9px;
  line-height: 1.55;
}

.drag-preview {
  position: fixed;
  z-index: 1000;
  width: 140px;
  transform: translate(-50%, -50%);
  pointer-events: none;
  padding: 9px 10px;
  border: 1px solid var(--silver);
  border-radius: 10px;
  background: rgba(255, 255, 255, .96);
  box-shadow: 0 13px 30px rgba(0, 0, 0, .16);
}

.drag-preview strong,
.drag-preview small {
  display: block;
}

.drag-preview strong {
  font-size: 10px;
}

.drag-preview small {
  margin-top: 2px;
  color: var(--dim);
  font-size: 8px;
  text-transform: uppercase;
}

.loading-card {
  padding: 40px;
  border-radius: 18px;
  text-align: center;
  color: var(--dim);
}

.result-overlay {
  position: fixed;
  z-index: 1100;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(0, 0, 0, .35);
  backdrop-filter: blur(4px);
}

.result-card {
  width: min(420px, 100%);
  padding: 27px;
  border-radius: 18px;
  text-align: center;
}

.result-card h2 {
  margin: 5px 0;
}

.result-score {
  display: block;
  margin: 10px 0;
  font-size: 42px;
  letter-spacing: -.06em;
}

.result-card p {
  color: var(--dim);
  font-size: 11px;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@media (max-width: 1120px) {
  .lab-layout {
    grid-template-columns: 1fr;
  }

  .bench-column {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .attempt-note {
    grid-column: 1/-1;
  }

  .chassis-svg {
    min-height: 480px;
  }
}

@media (max-width: 760px) {
  .page-shell {
    padding: 12px;
  }

  .page-header {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .performance-strip {
    width: 100%;
    grid-template-columns: repeat(4, 1fr);
  }

  .performance-strip>div {
    min-width: 0;
  }

  .lab-topline {
    flex-direction: column;
  }

  .submit-button {
    width: 100%;
  }

  .bench-column {
    grid-template-columns: 1fr;
  }

  .attempt-note {
    grid-column: auto;
  }

  .chassis-card {
    padding: 11px;
  }

  .chassis-svg {
    min-height: auto;
  }

  .tray-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 440px) {
  .performance-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .tray-list {
    grid-template-columns: 1fr;
  }

  .heading-copy h1 {
    font-size: 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .interactive-part {
    transition: none;
    animation: none !important;
  }
}
</style>

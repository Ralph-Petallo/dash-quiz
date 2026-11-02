# Hardware Procedure: Computer Assembly & Disassembly

## Valid Actions
- `unplug` / `unplug_ac_cable` — cable removal from port
- `disconnect` — internal cable/wire removal
- `unscrew` — fastener removal
- `remove` — hardware part removal
- `connect` — internal cable installation
- `plug` — AC cable installation
- `screw` — fastener installation
- `install` — hardware part installation

## Phase 1: DISASSEMBLE (execute in strict order)

| Step | Action | Component | Target Zone |
|---|---|---|---|
| 1 | `unplug` | ac_power_cable | ac_inlet |
| 2 | `unscrew` | side_panel_screws | side_screws |
| 3 | `remove` | side_panel | side_panel |
| 4 | `disconnect` | front_panel_wire | front_header |
| 5 | `disconnect` | card_reader_wire | reader_header |
| 6 | `disconnect` | sata_cable_hdd | sata_port |
| 7 | `disconnect` | portable_hdd_wires | dock_ports |
| 8 | `disconnect` | optical_ribbon | optical_header |
| 9 | `disconnect` | atx_24pin | atx_24pin |
| 10 | `disconnect` | cpu_power | cpu_power |
| 11 | `disconnect` | drive_power | drive_power |
| 12 | `disconnect` | gpu_power | gpu_power |
| 13 | `remove` | drive_slot_cover | drive_cover |
| 14 | `unscrew` | expansion_screws | exp_screws |
| 15 | `remove` | expansion_cards | pcie_slots |
| 16 | `remove` | expansion_covers | exp_covers |
| 17 | `remove` | ram | ram_slots |
| 18 | `disconnect` | cpu_fan_wire | cpu_fan_header |
| 19 | `unscrew` | cpu_fan_screws | cpu_fan_screws |
| 20 | `remove` | cpu_fan | cpu_fan_mount |
| 21 | `disconnect` | system_fan_wire | case_fan_header |
| 22 | `unscrew` | system_fan_screws | case_fan_screws |
| 23 | `remove` | system_fan | case_fan_mount |
| 24 | `unscrew` | card_reader_screws | reader_screws |
| 25 | `remove` | card_reader | reader_bay |
| 26 | `unscrew` | optical_screws | optical_screws |
| 27 | `remove` | optical_drive | optical_bay |
| 28 | `unscrew` | hdd_screws | hdd_screws |
| 29 | `remove` | hdd | hdd_bay |
| 30 | `remove` | portable_hdd_slot | dock_bay |
| 31 | `remove` | plastic_led_piece | led_mount |
| 32 | `unscrew` | io_screws | io_screws |
| 33 | `remove` | connectivity_center | io_center |
| 34 | `unscrew` | psu_screws | psu_screws |
| 35 | `remove` | psu | psu_bay |
| 36 | `unscrew` | motherboard_screws | mb_screws |
| 37 | `remove` | motherboard | mb_mount |

## Phase 2: ASSEMBLE (reverse order, all actions inverted)

- `remove` → `install` | `unscrew` → `screw` | `disconnect` → `connect` | `unplug` → `plug`
- Execute steps 37 → 1, mapping each column 2 value to its inverse.

## Constraints (agent rules)
- STRICT order: step N is only valid when steps 1..N-1 are complete.
- Component must be in its start state of the current phase.
- Destination zone MUST match the component's mapped zone.
- Never touch socket pins; never force a component; never power on without cooler.
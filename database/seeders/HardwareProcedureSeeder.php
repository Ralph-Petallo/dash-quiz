<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\DragDropItem;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HardwareProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::findOrFail(1);

        Assessment::updateOrCreate(
            [
                // assessments.coc_id references quizzes.id in this project.
                'coc_id' => $quiz->id,
                'type' => 'hardware_procedure',
            ],
            [
                'title' => 'Computer Assembly and Disassembly',
                'icon' => 'fa-computer',
                'description' => 'Unguided virtual practical assessment using a realistic system-unit chassis.',
                'category' => 'Computer Maintenance and Troubleshooting',
                'difficulty' => 'Medium',
                'meta' => json_encode([
                    'mode' => 'free_assessment',
                    'hidden_procedure' => true,
                    'disassembly_actions' => 37,
                    'assembly_actions' => 37,
                    'total_actions' => 74,
                    'passing_score' => 75,
                    'visual' => 'realistic_svg_system_unit',
                ]),
                'path' => "assemble",
                'available' => true,
            ]
        );

        $questionText = 'Complete the computer system unit practical assessment.';

        $question = Question::query()
            ->where('quiz_id', 1)
            ->where('question_text', $questionText)
            ->first();

        if (!$question) {
            $question = new Question();
            $question->quiz_id = 1;
        }

        $question->question_text = $questionText;

        if (Schema::hasColumn('questions', 'category')) {
            $question->category = 'Computer Maintenance and Troubleshooting';
        }
        if (Schema::hasColumn('questions', 'difficulty')) {
            $question->difficulty = 'medium';
        }
        if (Schema::hasColumn('questions', 'question_type')) {
            $question->question_type = 'drag_drop';
        }
        if (Schema::hasColumn('questions', 'type')) {
            $question->type = 'drag_drop';
        }

        $question->save();

        $disassembly = [
            $this->step('ac_power_cable', 'AC Power Cable', 'cable', 1, 'unplug', 'cable_tray', 'Disconnect external AC power before opening or servicing the system unit.'),
            $this->step('side_panel_screws', 'Side Panel Screws', 'fastener', 2, 'unscrew', 'screw_tray', 'Release the side-panel retaining screws.'),
            $this->step('side_panel', 'Side Panel', 'cover', 1, 'remove', 'parts_tray', 'Remove the side panel to expose the internal chassis.'),
            $this->step('front_panel_wire', 'Front Panel / LED Wire', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the button and LED/front-panel lead from the motherboard header.'),
            $this->step('card_reader_wire', 'Card Reader Accessory Wire', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the card reader accessory lead from the motherboard.'),
            $this->step('sata_cable_hdd', 'SATA Data Cable', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the hard-drive SATA data cable from the drive and motherboard.'),
            $this->step('portable_hdd_wires', 'Portable HDD Dock Wires', 'cable', 2, 'disconnect', 'cable_tray', 'Disconnect the portable HDD dock power and data connections.'),
            $this->step('optical_ribbon', 'CD/DVD Drive Ribbon', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the optical-drive ribbon/data connection from the motherboard.'),
            $this->step('atx_24pin', '24-pin ATX Power Lead', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the main motherboard power connector.'),
            $this->step('cpu_power', 'CPU Power Lead', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the CPU auxiliary power connector.'),
            $this->step('drive_power', 'Drive Power Lead', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect drive power from installed storage and optical devices.'),
            $this->step('gpu_power', 'Expansion Card Power Lead', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect auxiliary power from the expansion card when present.'),
            $this->step('drive_slot_cover', 'Drive Slot Cover', 'cover', 1, 'remove', 'parts_tray', 'Remove the front drive-slot cover.'),
            $this->step('expansion_screws', 'Expansion Card Screws', 'fastener', 2, 'unscrew', 'screw_tray', 'Remove the screws securing the expansion cards.'),
            $this->step('expansion_cards', 'Expansion Cards', 'hardware', 2, 'remove', 'parts_tray', 'Release the PCIe retention mechanism and remove the expansion cards.'),
            $this->step('expansion_covers', 'Expansion Slot Covers', 'cover', 2, 'remove', 'parts_tray', 'Remove the two expansion-card slot covers.'),
            $this->step('ram', 'RAM Chips', 'hardware', 2, 'remove', 'parts_tray', 'Release the DIMM clips and remove the memory modules.'),
            $this->step('cpu_fan_wire', 'CPU Fan Wire', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the CPU fan header before removing the cooler.'),
            $this->step('cpu_fan_screws', 'CPU Fan Screws', 'fastener', 4, 'unscrew', 'screw_tray', 'Release the CPU fan/cooler fasteners.'),
            $this->step('cpu_fan', 'Small CPU Fan', 'hardware', 1, 'remove', 'parts_tray', 'Lift the CPU cooling assembly away from the processor area.'),
            $this->step('system_fan_wire', 'System Fan Wire', 'cable', 1, 'disconnect', 'cable_tray', 'Disconnect the large chassis-fan power lead.'),
            $this->step('system_fan_screws', 'System Fan Screws', 'fastener', 4, 'unscrew', 'screw_tray', 'Release the large system fan fasteners.'),
            $this->step('system_fan', 'Large System Fan', 'hardware', 1, 'remove', 'parts_tray', 'Remove the large chassis fan.'),
            $this->step('card_reader_screws', 'Card Reader Screws', 'fastener', 2, 'unscrew', 'screw_tray', 'Release the card-reader mounting screws.'),
            $this->step('card_reader', 'Card Reader', 'hardware', 1, 'remove', 'parts_tray', 'Slide the card reader out of its front bay.'),
            $this->step('optical_screws', 'CD/DVD Drive Screws', 'fastener', 2, 'unscrew', 'screw_tray', 'Release the optical-drive mounting screws.'),
            $this->step('optical_drive', 'CD/DVD Drive', 'hardware', 1, 'remove', 'parts_tray', 'Slide the optical drive out of its bay.'),
            $this->step('hdd_screws', 'Hard Drive Screws', 'fastener', 4, 'unscrew', 'screw_tray', 'Release the hard-drive mounting screws.'),
            $this->step('hdd', 'Hard Drive', 'hardware', 1, 'remove', 'parts_tray', 'Remove the hard drive from the drive cage.'),
            $this->step('portable_hdd_slot', 'Portable HDD Slot', 'hardware', 1, 'remove', 'parts_tray', 'Remove the portable-HDD docking slot from the chassis.'),
            $this->step('plastic_led_piece', 'Button / LED Plastic Piece', 'hardware', 1, 'remove', 'parts_tray', 'Remove the plastic chassis piece supporting the power button and LEDs.'),
            $this->step('io_screws', 'Connectivity Center Screws', 'fastener', 2, 'unscrew', 'screw_tray', 'Release the connectivity-center mounting screws.'),
            $this->step('connectivity_center', 'Connectivity Center', 'hardware', 1, 'remove', 'parts_tray', 'Remove the front connectivity center / I/O assembly.'),
            $this->step('psu_screws', 'Power Supply Screws', 'fastener', 4, 'unscrew', 'screw_tray', 'Release the power-supply mounting screws.'),
            $this->step('psu', 'Power Supply', 'hardware', 1, 'remove', 'parts_tray', 'Remove the power supply from the PSU bay.'),
            $this->step('motherboard_screws', 'Motherboard Screws', 'fastener', 7, 'unscrew', 'screw_tray', 'Release the motherboard mounting screws from the standoffs.'),
            $this->step('motherboard', 'Motherboard', 'hardware', 1, 'remove', 'parts_tray', 'Lift the motherboard away from the chassis standoffs and rear I/O opening.'),
        ];

        $assembly = [
            $this->step('motherboard', 'Motherboard', 'hardware', 1, 'install', 'mb_mount', 'Seat the motherboard on the standoffs and align the rear I/O area.'),
            $this->step('motherboard_screws', 'Motherboard Screws', 'fastener', 7, 'screw', 'mb_screws', 'Secure the motherboard to the chassis standoffs.'),
            $this->step('psu', 'Power Supply', 'hardware', 1, 'install', 'psu_bay', 'Install the power supply in the PSU bay.'),
            $this->step('psu_screws', 'Power Supply Screws', 'fastener', 4, 'screw', 'psu_screws', 'Secure the power supply to the chassis.'),
            $this->step('connectivity_center', 'Connectivity Center', 'hardware', 1, 'install', 'io_center', 'Install the front connectivity center / I/O assembly.'),
            $this->step('io_screws', 'Connectivity Center Screws', 'fastener', 2, 'screw', 'io_screws', 'Secure the connectivity-center mounting points.'),
            $this->step('plastic_led_piece', 'Button / LED Plastic Piece', 'hardware', 1, 'install', 'led_mount', 'Install the plastic button and LED support piece.'),
            $this->step('portable_hdd_slot', 'Portable HDD Slot', 'hardware', 1, 'install', 'dock_bay', 'Install the portable-HDD docking slot.'),
            $this->step('hdd', 'Hard Drive', 'hardware', 1, 'install', 'hdd_bay', 'Install the hard drive into its drive bay.'),
            $this->step('hdd_screws', 'Hard Drive Screws', 'fastener', 4, 'screw', 'hdd_screws', 'Secure the hard drive in the drive cage.'),
            $this->step('optical_drive', 'CD/DVD Drive', 'hardware', 1, 'install', 'optical_bay', 'Install the optical drive in the front drive bay.'),
            $this->step('optical_screws', 'CD/DVD Drive Screws', 'fastener', 2, 'screw', 'optical_screws', 'Secure the optical drive.'),
            $this->step('card_reader', 'Card Reader', 'hardware', 1, 'install', 'reader_bay', 'Install the card reader in its front bay.'),
            $this->step('card_reader_screws', 'Card Reader Screws', 'fastener', 2, 'screw', 'reader_screws', 'Secure the card reader.'),
            $this->step('system_fan', 'Large System Fan', 'hardware', 1, 'install', 'case_fan_mount', 'Install the large chassis fan.'),
            $this->step('system_fan_screws', 'System Fan Screws', 'fastener', 4, 'screw', 'case_fan_screws', 'Secure the large system fan.'),
            $this->step('system_fan_wire', 'System Fan Wire', 'cable', 1, 'connect', 'case_fan_header', 'Reconnect the large system-fan power lead.'),
            $this->step('cpu_fan', 'Small CPU Fan', 'hardware', 1, 'install', 'cpu_fan_mount', 'Install the CPU cooling assembly.'),
            $this->step('cpu_fan_screws', 'CPU Fan Screws', 'fastener', 4, 'screw', 'cpu_fan_screws', 'Secure the CPU cooling assembly.'),
            $this->step('cpu_fan_wire', 'CPU Fan Wire', 'cable', 1, 'connect', 'cpu_fan_header', 'Reconnect the CPU fan header.'),
            $this->step('ram', 'RAM Chips', 'hardware', 2, 'install', 'ram_slots', 'Install the RAM modules into the DIMM slots.'),
            $this->step('expansion_covers', 'Expansion Slot Covers', 'cover', 2, 'install', 'exp_covers', 'Install the required expansion-slot covers.'),
            $this->step('expansion_cards', 'Expansion Cards', 'hardware', 2, 'install', 'pcie_slots', 'Install the expansion cards into their slots.'),
            $this->step('expansion_screws', 'Expansion Card Screws', 'fastener', 2, 'screw', 'exp_screws', 'Secure the expansion cards.'),
            $this->step('drive_slot_cover', 'Drive Slot Cover', 'cover', 1, 'install', 'drive_cover', 'Install the front drive-slot cover.'),
            $this->step('gpu_power', 'Expansion Card Power Lead', 'cable', 1, 'connect', 'gpu_power', 'Reconnect auxiliary power to the expansion card when used.'),
            $this->step('drive_power', 'Drive Power Lead', 'cable', 1, 'connect', 'drive_power', 'Reconnect drive power.'),
            $this->step('cpu_power', 'CPU Power Lead', 'cable', 1, 'connect', 'cpu_power', 'Reconnect the CPU auxiliary power connector.'),
            $this->step('atx_24pin', '24-pin ATX Power Lead', 'cable', 1, 'connect', 'atx_24pin', 'Reconnect the motherboard main power connector.'),
            $this->step('optical_ribbon', 'CD/DVD Drive Ribbon', 'cable', 1, 'connect', 'optical_header', 'Reconnect the optical-drive ribbon/data connection.'),
            $this->step('portable_hdd_wires', 'Portable HDD Dock Wires', 'cable', 2, 'connect', 'dock_ports', 'Reconnect the portable HDD dock power and data leads.'),
            $this->step('sata_cable_hdd', 'SATA Data Cable', 'cable', 1, 'connect', 'sata_port', 'Reconnect the hard-drive SATA data cable.'),
            $this->step('card_reader_wire', 'Card Reader Accessory Wire', 'cable', 1, 'connect', 'reader_header', 'Reconnect the card-reader accessory lead.'),
            $this->step('front_panel_wire', 'Front Panel / LED Wire', 'cable', 1, 'connect', 'front_header', 'Reconnect the front-panel button and LED lead.'),
            $this->step('side_panel', 'Side Panel', 'cover', 1, 'install', 'side_panel', 'Install the system-unit side panel.'),
            $this->step('side_panel_screws', 'Side Panel Screws', 'fastener', 2, 'screw', 'side_screws', 'Secure the side panel.'),
            $this->step('ac_power_cable', 'AC Power Cable', 'cable', 1, 'plug', 'ac_inlet', 'Reconnect external AC power only after reassembly is complete.'),
        ];

        $this->seedPhase($question->id, 'disassemble', $disassembly);
        $this->seedPhase($question->id, 'assemble', $assembly);
    }

    private function step(
        string $key,
        string $name,
        string $type,
        int $quantity,
        string $action,
        string $targetZone,
        string $hiddenInstruction,
        ?string $image = null,
    ): array {
        return [
            'component_key' => $key,
            'display_name' => $name,
            'item_type' => $type,
            'quantity' => $quantity,
            'action' => $action,
            'target_zone' => $targetZone,
            'instruction' => $hiddenInstruction,
            'image' => $image,
        ];
    }

    private function seedPhase(int $questionId, string $phase, array $steps): void
    {
        foreach ($steps as $index => $step) {
            $position = $index + 1;

            DragDropItem::updateOrCreate(
                [
                    'question_id' => $questionId,
                    'phase' => $phase,
                    'correct_position' => $position,
                ],
                [
                    'component_key' => $step['component_key'],
                    'display_name' => $step['display_name'],
                    'item_type' => $step['item_type'],
                    'quantity' => $step['quantity'],
                    'item_text' => ucfirst($step['action']) . ' ' . $step['display_name'],
                    'instruction' => $step['instruction'],
                    'item_image_path' => $step['image'],
                    'action' => $step['action'],
                    'target_zone' => $step['target_zone'],
                    'required' => true,
                    'active' => true,
                ]
            );
        }

        DragDropItem::query()
            ->where('question_id', $questionId)
            ->where('phase', $phase)
            ->where('correct_position', '>', count($steps))
            ->delete();
    }
}

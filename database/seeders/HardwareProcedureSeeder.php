<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Assessment;
use App\Models\DragDropItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HardwareProcedureSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * |--------------------------------------------------------------------------
         * | DISASSEMBLY PROCEDURE
         * |--------------------------------------------------------------------------
         */

        $disassembly = [
            $this->step(
                'side_panel',
                'Side Cover',
                'cover',
                1,
                'remove',
                'parts_tray',
                'Turn off the computer, unplug the power cord and peripherals, wear an anti-static wrist strap, then unscrew and slide off the side cover.'
            ),
            $this->step(
                'internal_cables',
                'Internal Cables and Connectors',
                'cable',
                1,
                'disconnect',
                'cable_tray',
                'Disconnect the internal cables and connectors, including the ATX power, CPU power, SATA/data, and front-panel cables.'
            ),
            $this->step(
                'system_fan',
                'System Fans',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Unscrew and carefully remove the system fans from the chassis.'
            ),
            $this->step(
                'expansion_card',
                'Expansion Cards',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Unlock the PCIe slot retention clip and carefully remove the expansion cards.'
            ),
            $this->step(
                'ram',
                'RAM',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Push down the RAM retention tabs and carefully remove the memory sticks.'
            ),
            $this->step(
                'cpu_heatsink',
                'CPU and Heat Sink',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Disconnect the CPU fan cable, remove the heat sink, release the CPU socket lever, and carefully remove the CPU.'
            ),
            $this->step(
                'storage_drive',
                'HDD/SSD and Optical Drive',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Unscrew and carefully slide the HDD/SSD and optical drive out of their bays.'
            ),
            $this->step(
                'psu',
                'Power Supply Unit',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Unscrew and carefully slide the power supply unit out of the case.'
            ),
            $this->step(
                'motherboard',
                'Motherboard',
                'hardware',
                1,
                'remove',
                'parts_tray',
                'Remove the motherboard mounting screws and carefully lift the motherboard out of the case.'
            ),
        ];

        /*
         * |--------------------------------------------------------------------------
         * | ASSEMBLY PROCEDURE
         * |--------------------------------------------------------------------------
         */

        $assembly = [
            $this->step(
                'motherboard',
                'Motherboard',
                'hardware',
                1,
                'install',
                'case',
                'Carefully place the motherboard into the chassis, align it with the standoffs and rear I/O opening, and secure it.'
            ),
            $this->step(
                'psu',
                'Power Supply Unit',
                'hardware',
                1,
                'install',
                'case',
                'Slide the power supply unit into the PSU bay and secure it with the mounting screws.'
            ),
            $this->step(
                'storage_drive',
                'HDD/SSD and Optical Drive',
                'hardware',
                1,
                'install',
                'case',
                'Slide the HDD/SSD and optical drive into their appropriate bays and secure them.'
            ),
            $this->step(
                'cpu_heatsink',
                'CPU and Heat Sink',
                'hardware',
                1,
                'install',
                'case',
                'Carefully install the CPU, lock the socket lever, install the heat sink, and reconnect the CPU fan cable.'
            ),
            $this->step(
                'ram',
                'RAM',
                'hardware',
                1,
                'install',
                'case',
                'Align the RAM modules with the DIMM slots and press them down until the retention tabs lock into place.'
            ),
            $this->step(
                'expansion_card',
                'Expansion Cards',
                'hardware',
                1,
                'install',
                'case',
                'Insert the expansion cards into their appropriate PCIe slots and secure them.'
            ),
            $this->step(
                'system_fan',
                'System Fans',
                'hardware',
                1,
                'install',
                'case',
                'Install and secure the system fans in their proper mounting positions.'
            ),
            $this->step(
                'internal_cables',
                'Internal Cables and Connectors',
                'cable',
                1,
                'connect',
                'case',
                'Reconnect the ATX power, CPU power, SATA/data, front-panel, and other required internal cables and connectors.'
            ),
            $this->step(
                'side_panel',
                'Side Cover',
                'cover',
                1,
                'install',
                'case',
                'Slide the side cover back into position and secure it with the screws. Reconnect external peripherals and the power cord only after the case is closed.'
            ),
        ];

        /*
         * |--------------------------------------------------------------------------
         * | GET COC 1 QUIZ
         * |--------------------------------------------------------------------------
         */


        $quiz = Quiz::query()
            ->where('coc_number', 1)
            ->firstOrFail();

        $assessment = Assessment::updateOrCreate(
            [
                'coc_id' => $quiz->id,
                'type' => 'hardware_procedure',
            ],
            [
                'title' => 'Assembly and Disassembly',
                'icon' => 'fa-solid fa-computer',
                'description' => 'Unguided virtual practical assessment using a realistic system-unit chassis.',
                'category' => 'Computer Maintenance and Troubleshooting',
                'difficulty' => 'Medium',
                'meta' => [
                    'mode' => 'guided_practical',
                    'hidden_procedure' => true,
                    'disassembly_actions' => count($disassembly),
                    'assembly_actions' => count($assembly),
                    'total_actions' => count($disassembly) + count($assembly),
                    'passing_score' => 75,
                ],
                'path' => '/taking/assemble',
                'available' => true,
            ]
        );

        $question = Question::firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'question_text' => 'Complete the computer system unit practical assessment.',
            ]
        );

        if (Schema::hasColumn('questions', 'question_type')) {
            $question->question_type = 'drag_drop';
            $question->save();
        }

        $this->seedPhase($question->id, 'disassemble', $disassembly);
        $this->seedPhase($question->id, 'assemble', $assembly);

        $this->command->info("Hardware procedure seeded for assessment #{$assessment->id}.");
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
        $hasInstruction = Schema::hasColumn('drag_drop_items', 'instruction');
        $hasItemText = Schema::hasColumn('drag_drop_items', 'item_text');
        $hasImage = Schema::hasColumn('drag_drop_items', 'item_image_path');

        foreach ($steps as $index => $step) {
            $values = [
                'display_name' => $step['display_name'],
                'item_type' => $step['item_type'],
                'quantity' => $step['quantity'],
                'action' => $step['action'],
                'target_zone' => $step['target_zone'],
                'required' => true,
                'active' => true,
            ];

            if ($hasInstruction) {
                $values['instruction'] = $step['instruction'];
            }

            if ($hasItemText) {
                $values['item_text'] = ucfirst($step['action']) . ' ' . $step['display_name'];
            }

            if ($hasImage) {
                $values['item_image_path'] = $step['image'];
            }

            DragDropItem::updateOrCreate(
                [
                    'question_id' => $questionId,
                    'phase' => $phase,
                    'correct_position' => $index + 1,
                ],
                array_merge($values, ['component_key' => $step['component_key']])
            );
        }

        // Remove steps that are no longer part of the procedure.
        DragDropItem::query()
            ->where('question_id', $questionId)
            ->where('phase', $phase)
            ->where('correct_position', '>', count($steps))
            ->delete();
    }
}

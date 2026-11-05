<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\DragDropItem;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class HardwareProcedureSeeder extends Seeder
{
    private const HARDWARE_TYPE = 'hardware_procedure';
    private const QUESTION_TYPE = 'drag_drop';
    private const QUESTION_TEXT = 'Complete the computer system unit practical assessment.';

    public function run(): void
    {
        $quiz = Quiz::query()->where('coc_number', 1)->firstOrFail();

        $assessment = Assessment::query()->updateOrCreate(
            ['coc_id' => $quiz->id, 'type' => self::HARDWARE_TYPE],
            [
                'title' => 'Assembly and Disassembly',
                'icon' => 'fa-solid fa-computer',
                'description' => 'Virtual practical: prepare safely, take the system unit apart, put it back together, then test it.',
                'category' => 'Computer Maintenance and Troubleshooting',
                'difficulty' => 'Medium',
                'meta' => [
                    'assessment_format' => 'Virtual practical',
                    'passing_score' => 75,
                ],
                'path' => '/taking/hardware-procedure',
                'available' => true,
            ]
        );

        $question = Question::query()->firstOrCreate(
            ['quiz_id' => $quiz->id, 'question_text' => self::QUESTION_TEXT]
        );

        $question->question_type = self::QUESTION_TYPE;
        $question->save();

        $this->seedSteps($question->id, $this->steps());

        $this->command?->info("Hardware procedure seeded for assessment #{$assessment->id}.");
    }

    /** The full procedure in the order a learner must follow it. */
    private function steps(): array
    {
        return [
            // PREPARE: safety checklist before any part is touched.
            $this->prepStep('power_off', 'Turn off the computer',
                'Shut down the computer from the operating system, then switch off the power supply at the back.'),
            $this->prepStep('unplug_power', 'Unplug the power cord from the wall outlet',
                'Remove the power cord from the back of the case and from the wall.'),
            $this->prepStep('disconnect_peripherals', 'Disconnect monitor, keyboard, mouse, and network cable',
                'Disconnect every external cable so nothing is attached to the system unit.'),
            $this->prepStep('gather_tools', 'Gather a Phillips screwdriver and a screw container',
                'Keep the tools and a container for the screws within reach before you open the case.'),
            $this->prepStep('prepare_workspace', 'Move the system unit to a clean, non-carpeted table',
                'Place the unit on a clean table that is not carpeted, so static is less likely to build up.'),
            $this->prepStep('wear_wrist_strap', 'Put on the anti-static wrist strap',
                'Wear the strap on your wrist before you touch any internal part.'),
            $this->prepStep('ground_wrist_strap', 'Clip the strap to a grounded metal surface',
                'Attach the clip to unpainted metal on the chassis or another grounded surface.'),

            // DISASSEMBLE
            $this->partStep(DragDropItem::DISASSEMBLE, 'side_panel', 'Side panel',
                'Unscrew the side panel and slide it off the case.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'internal_cables', 'Internal cables and connectors',
                'Disconnect the ATX power, CPU power, SATA data, and front-panel cables.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'system_fan', 'System fan',
                'Unscrew and carefully remove the system fans.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'expansion_card', 'Expansion card',
                'Unlock the PCIe retention clip and remove the expansion cards.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'ram', 'RAM',
                'Push down the RAM retention tabs and lift out each memory module.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'cpu_heatsink', 'CPU heatsink',
                'Disconnect the CPU fan cable, remove the heatsink, release the CPU lever, and lift out the CPU.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'storage_drive', 'Storage drive',
                'Unscrew and slide out the HDD, SSD, and optical drive.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'psu', 'Power supply',
                'Unscrew and slide out the power supply unit.'),
            $this->partStep(DragDropItem::DISASSEMBLE, 'motherboard', 'Motherboard',
                'Remove the mounting screws and lift the motherboard out of the case.'),

            // ASSEMBLE
            $this->partStep(DragDropItem::ASSEMBLE, 'motherboard', 'Motherboard',
                'Align the motherboard with the standoffs and rear I/O opening, then secure it.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'psu', 'Power supply',
                'Slide the power supply into its bay and secure it with screws.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'storage_drive', 'Storage drive',
                'Slide the drives into their bays and secure them.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'cpu_heatsink', 'CPU heatsink',
                'Install the CPU, lock the lever, fit the heatsink, and reconnect the CPU fan.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'ram', 'RAM',
                'Align each module with its DIMM slot and press until the tabs lock.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'expansion_card', 'Expansion card',
                'Insert the expansion cards into their PCIe slots and secure them.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'system_fan', 'System fan',
                'Mount the system fans in their positions and secure them.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'internal_cables', 'Internal cables and connectors',
                'Reconnect the ATX power, CPU power, SATA data, and front-panel cables.'),
            $this->partStep(DragDropItem::ASSEMBLE, 'side_panel', 'Side panel',
                'Slide the side panel back on and secure it. Reconnect peripherals only after the case is closed.'),

            // FINISH: checklist after the chassis is closed.
            $this->finishStep('reconnect_peripherals', 'Reconnect monitor, keyboard, mouse, and network cable',
                'Connect the peripherals and network cable back to the system unit.'),
            $this->finishStep('plug_power', 'Plug in the power cord and switch on the power supply',
                'Connect the power cord to the wall outlet and switch on the power supply at the back.'),
            $this->finishStep('power_on_test', 'Turn on the computer and check that it starts',
                'Power on the computer and confirm that it starts without errors.'),
        ];
    }

    private function prepStep(string $key, string $label, string $instruction): array
    {
        return [
            'phase' => DragDropItem::PREPARE,
            'component_key' => $key,
            'display_name' => $label,
            'item_type' => 'prep',
            'action' => 'perform',
            'target_zone' => 'none',
            'instruction' => $instruction,
        ];
    }

    private function finishStep(string $key, string $label, string $instruction): array
    {
        return [
            'phase' => DragDropItem::FINISH,
            'component_key' => $key,
            'display_name' => $label,
            'item_type' => 'finish',
            'action' => 'perform',
            'target_zone' => 'none',
            'instruction' => $instruction,
        ];
    }

    private function partStep(string $phase, string $key, string $label, string $instruction): array
    {
        $isDisassembly = $phase === DragDropItem::DISASSEMBLE;

        return [
            'phase' => $phase,
            'component_key' => $key,
            'display_name' => $label,
            'item_type' => 'component',
            'action' => $isDisassembly ? 'remove' : 'install',
            'target_zone' => $isDisassembly
                ? ($key === 'internal_cables' ? 'cable_tray' : 'parts_tray')
                : 'case',
            'instruction' => $instruction,
        ];
    }

    /** Write steps in order, numbering each phase from 1. */
    private function seedSteps(int $questionId, array $steps): void
    {
        $positions = [];
        $keptIds = [];

        foreach ($steps as $step) {
            $phase = $step['phase'];
            $positions[$phase] = ($positions[$phase] ?? 0) + 1;

            $item = DragDropItem::query()->updateOrCreate(
                [
                    'question_id' => $questionId,
                    'phase' => $phase,
                    'correct_position' => $positions[$phase],
                ],
                [
                    'component_key' => $step['component_key'],
                    'display_name' => $step['display_name'],
                    'item_type' => $step['item_type'],
                    'quantity' => 1,
                    'item_text' => $step['display_name'],
                    'instruction' => $step['instruction'],
                    'action' => $step['action'],
                    'target_zone' => $step['target_zone'],
                    'required' => true,
                    'active' => true,
                ]
            );

            $keptIds[] = $item->id;
        }

        // Remove steps no longer in the procedure. Steps that learners have used are kept
        // so their attempt records stay valid.
        DragDropItem::query()
            ->where('question_id', $questionId)
            ->whereNotIn('id', $keptIds)
            ->whereDoesntHave('attemptSteps')
            ->delete();
    }
}
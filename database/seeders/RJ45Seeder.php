<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Rj45Assessment;
use App\Models\Rj45Pinout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RJ45Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $quiz = Quiz::updateOrCreate(
                ['title' => 'RJ-45 Cable Termination'],
                [
                    'description' => 'Arrange, insert, crimp, test, and submit an RJ-45 cable termination attempt.',
                    'quiz_type' => 'rj45_hands_on',
                    'coc_number' => 1,
                    'difficulty' => 'Medium',
                    'category' => 'Network Cabling',
                ]
            );

            /*
             * AssessmentTypeController currently treats assessments.coc_id
             * as the COC number, not quizzes.id. Keep this aligned with it.
             */
            $assessment = Assessment::updateOrCreate(
                [
                    'coc_id' => $quiz->coc_number,
                    'type' => 'rj45_hands_on',
                    'title' => 'RJ-45 Cable Termination',
                ],
                [
                    'icon' => 'fa-solid fa-network-wired',
                    'description' => 'Practical RJ-45 cable termination and pin-position testing activity.',
                    'category' => 'Network Cabling',
                    'difficulty' => 'Medium',
                    'meta' => json_encode([
                        'quiz_id' => $quiz->coc_number,
                        'standard_options' => ['A', 'B'],
                        'pins' => 8,
                    ]),
                    // Change only if your Vue router uses a different path.
                    'path' => '/user/quizzes/rj45/' . $quiz->coc_number,
                    'available' => true,
                ]
            );

            $rj45 = Rj45Assessment::updateOrCreate(
                ['quiz_id' => $quiz->coc_number],
                [
                    'assessment_id' => $assessment->id,
                    'app_name' => 'CSS Prep',
                    'project_title' => 'CSS Prep: A Web-Based Competency Assessment and Performance Evaluation System for NC II Readiness',
                    'project_subtitle' => 'NC II Readiness Practical Assessment',
                    'title' => 'RJ-45 Cable Termination',
                    'description' => 'Arrange all eight conductors, insert and crimp the cable, test the pin positions, then submit the attempt for recording.',
                    'coc_label' => 'COC 1 / Practical Bench',
                    'default_standard' => 'B',
                    'test_pin_delay_ms' => 0,
                    'tray_order' => ['wo', 'o', 'wg', 'b', 'wb', 'g', 'wbr', 'br'],
                    'initial_wiring' => [null, null, null, null, null, null, null, null],
                    'simulation_note' => 'This virtual activity evaluates the displayed conductor order. Every submitted attempt is recorded whether passed or failed.',
                    'is_active' => true,
                ]
            );

            $wireInfo = [
                'wo' => ['White-Orange', 'W/O', '#D87419', true],
                'o' => ['Orange', 'O', '#D87419', false],
                'wg' => ['White-Green', 'W/G', '#27824B', true],
                'g' => ['Green', 'G', '#27824B', false],
                'b' => ['Blue', 'B', '#286FB7', false],
                'wb' => ['White-Blue', 'W/B', '#286FB7', true],
                'wbr' => ['White-Brown', 'W/Br', '#865336', true],
                'br' => ['Brown', 'Br', '#865336', false],
            ];

            $pinouts = [
                'A' => ['wg', 'g', 'wo', 'b', 'wb', 'o', 'wbr', 'br'],
                'B' => ['wo', 'o', 'wg', 'b', 'wb', 'g', 'wbr', 'br'],
            ];

            foreach ($pinouts as $standard => $order) {
                foreach ($order as $index => $wireCode) {
                    [$wireName, $shortLabel, $colorHex, $striped] = $wireInfo[$wireCode];

                    Rj45Pinout::updateOrCreate(
                        [
                            'rj45_assessment_id' => $rj45->id,
                            'standard' => $standard,
                            'pin_number' => $index + 1,
                        ],
                        [
                            'wire_code' => $wireCode,
                            'wire_name' => $wireName,
                            'short_label' => $shortLabel,
                            'color_hex' => $colorHex,
                            'striped' => $striped,
                        ]
                    );
                }
            }
        });
    }
}

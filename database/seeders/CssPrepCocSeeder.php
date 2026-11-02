<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CssPrepCocSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $cocs = [
                1 => [
                    'title' => 'Install and Configure Computer Systems',
                    'description' => 'Build readiness in computer assembly, hardware installation, BIOS setup, operating-system installation, drivers, applications and system testing.',
                    'category' => 'Computer Systems Installation',
                    'difficulty' => 'Core',
                ],
                2 => [
                    'title' => 'Set Up Computer Networks',
                    'description' => 'Build readiness in network cabling, copper termination, network configuration, connectivity checking and basic router or wireless setup.',
                    'category' => 'Computer Networking',
                    'difficulty' => 'Core',
                ],
                3 => [
                    'title' => 'Set Up Computer Servers',
                    'description' => 'Build readiness in user access, network services, server configuration, security checking, testing and pre-deployment procedures.',
                    'category' => 'Computer Server Administration',
                    'difficulty' => 'Core',
                ],
                4 => [
                    'title' => 'Maintain and Repair Computer Systems and Networks',
                    'description' => 'Build readiness in preventive maintenance, fault diagnosis, corrective action, repair, replacement and verification of computer systems and networks.',
                    'category' => 'Systems and Network Maintenance',
                    'difficulty' => 'Core',
                ],
            ];

            $saved = [];

            foreach ($cocs as $number => $data) {
                $saved[$number] = Quiz::updateOrCreate(
                    [
                        'quiz_type' => 'coc',
                        'coc_number' => $number,
                    ],
                    [
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'difficulty' => $data['difficulty'],
                        'category' => $data['category'],
                    ]
                );
            }

            /*
            |------------------------------------------------------------------
            | COC 1 - implemented hardware practical
            |------------------------------------------------------------------
            */
            Assessment::updateOrCreate(
                [
                    'coc_id' => $saved[1]->id,
                    'type' => 'hardware_procedure',
                ],
                [
                    'title' => 'Computer Assembly and Disassembly',
                    'icon' => 'fa-solid fa-screwdriver-wrench',
                    'description' => 'Unguided virtual practical for safely disassembling and assembling a desktop system unit.',
                    'category' => 'Hardware Installation',
                    'difficulty' => 'Practical',
                    'meta' => [
                        'assessment_format' => 'Virtual hands-on',
                        'focus' => [
                            'Safety preparation',
                            'Hardware removal',
                            'Hardware installation',
                            'Cable and component handling',
                        ],
                        'total_actions' => 74,
                        'passing_score' => 75,
                        'self_paced' => true,
                        'readiness_context' => 'CSS NC II COC 1 practice',
                    ],
                    'path' => "/user/quizzes/{$saved[1]->id}/hardware-procedure",
                    'available' => true,
                ]
            );

            /*
            |------------------------------------------------------------------
            | COC 2 - implemented RJ-45 practical
            |------------------------------------------------------------------
            */
            Assessment::updateOrCreate(
                [
                    'coc_id' => $saved[2]->id,
                    'type' => 'rj45_hands_on',
                ],
                [
                    'title' => 'RJ-45 Cable Termination',
                    'icon' => 'fa-solid fa-network-wired',
                    'description' => 'Arrange and terminate all eight conductors using T568A or T568B, then test the completed pin order.',
                    'category' => 'Network Cabling',
                    'difficulty' => 'Practical',
                    'meta' => [
                        'assessment_format' => 'Virtual hands-on',
                        'standards' => ['T568A', 'T568B'],
                        'pins' => 8,
                        'passing_score' => 100,
                        'self_paced' => true,
                        'readiness_context' => 'CSS NC II COC 2 practice',
                    ],
                    'path' => "/user/quizzes/rj45/{$saved[2]->id}",
                    'available' => true,
                ]
            );

            /*
            |------------------------------------------------------------------
            | Planned assessments - visible as future CSS Prep coverage
            |------------------------------------------------------------------
            | They remain unavailable until their actual assessment pages and
            | scoring flows are implemented.
            */
            $planned = [
                [
                    'coc' => 1,
                    'type' => 'knowledge_check',
                    'title' => 'Installation and Configuration Knowledge Check',
                    'icon' => 'fa-solid fa-list-check',
                    'description' => 'Theory check for installation preparation, BIOS, operating systems, drivers, applications and testing.',
                    'category' => 'System Installation',
                ],
                [
                    'coc' => 2,
                    'type' => 'knowledge_check',
                    'title' => 'Network Setup Knowledge Check',
                    'icon' => 'fa-solid fa-diagram-project',
                    'description' => 'Theory check for cable planning, NIC configuration, connectivity, router and wireless setup.',
                    'category' => 'Network Configuration',
                ],
                [
                    'coc' => 3,
                    'type' => 'knowledge_check',
                    'title' => 'Server Setup Knowledge Check',
                    'icon' => 'fa-solid fa-server',
                    'description' => 'Theory check for user access, network services, security, testing and pre-deployment procedures.',
                    'category' => 'Server Administration',
                ],
                [
                    'coc' => 4,
                    'type' => 'knowledge_check',
                    'title' => 'Maintenance and Troubleshooting Knowledge Check',
                    'icon' => 'fa-solid fa-toolbox',
                    'description' => 'Theory check for maintenance planning, diagnosis, repair, corrective action and verification.',
                    'category' => 'Maintenance and Troubleshooting',
                ],
            ];

            foreach ($planned as $item) {
                Assessment::updateOrCreate(
                    [
                        'coc_id' => $saved[$item['coc']]->id,
                        'type' => $item['type'],
                        'title' => $item['title'],
                    ],
                    [
                        'icon' => $item['icon'],
                        'description' => $item['description'],
                        'category' => $item['category'],
                        'difficulty' => 'Knowledge Check',
                        'meta' => [
                            'assessment_format' => 'Theory',
                            'self_paced' => true,
                            'readiness_context' => 'CSS NC II readiness practice',
                        ],
                        'path' => 'secret anay ang path',
                        'available' => false,
                    ]
                );
            }
        });
    }
}

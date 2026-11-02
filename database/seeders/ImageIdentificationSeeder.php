<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ImageIdentificationItem;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class ImageIdentificationSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::query()
            ->where('coc_number', 1)
            ->firstOrFail();

        $assessment = Assessment::updateOrCreate(
            [
                'coc_id' => $quiz->id,
                'type' => 'image_identification',
            ],
            [
                'title' => 'Components Identification',
                'icon' => 'fa-solid fa-microchip',
                'description' => 'Identify common computer components, connectors, and accessories.',
                'category' => 'Hardware',
                'difficulty' => 'Medium',
                'meta' => ['18 Images'],
                'path' => '/taking/image-identification',
                'available' => true,
            ]
        );

        // [image file, item name, description shown as the answer choice]
        // Descriptions state the function only, in technical wording, so the image is the clue.
        $items = [
            ['cmos-battery.jpg', 'CMOS Battery', 'Maintains the date, time and firmware configuration when the mains supply is disconnected'],
            ['cpu-cooler.jpg', 'CPU Cooler', 'Transfers thermal energy from the central processor into ambient air through a finned conductor'],
            ['cpu.jpg', 'CPU', 'Fetches and executes machine-level instructions by cycling through an arithmetic and logic unit'],
            ['dvd-cd_drive.jpg', 'DVD/CD Drive', 'Retrieves data from reflective polycarbonate discs by detecting a focused beam’s reflection'],
            ['ethernet.jpg', 'Ethernet Port', 'Terminates a twisted-pair link to provide Layer 1 and Layer 2 connectivity to a LAN segment'],
            ['gpu.jpg', 'GPU', 'Converts scene geometry and textures into a rasterised frame buffer for the display output'],
            ['hdd.jpg', 'HDD', 'Persists data magnetically on rotating platters accessed by a moving actuator arm'],
            ['hdmi.jpg', 'HDMI Port', 'Carries uncompressed digital video and multichannel audio over a single transition-minimised cable'],
            ['motherboard.jpg', 'Motherboard', 'Hosts the chipset and expansion slots, distributing power and signals between all components'],
            ['nic.jpg', 'NIC', 'Adds a network interface over a PCI or PCIe bus, handling frame transmission and reception'],
            ['psu.jpg', 'PSU', 'Rectifies and regulates incoming AC into multiple DC rails such as +12V, +5V and +3.3V'],
            ['ram.jpg', 'RAM', 'Provides volatile, byte-addressable storage that loses its contents when power is removed'],
            ['sata.jpg', 'SATA Port', 'Provides a serial interface that transfers data between a host controller and a storage device'],
            ['sound-card.jpg', 'Sound Card', 'Performs digital-to-analogue conversion of audio samples for line-level and headphone output'],
            ['ssd.jpg', 'SSD', 'Stores data in NAND flash cells with no rotating or moving mechanical parts'],
            ['System_unit.jpg', 'System Unit', 'Houses the internal components within a chassis that provides airflow and mounting points'],
            ['usb-a.jpg', 'USB-A Port', 'Standard rectangular host-side connector for attaching peripheral devices over a serial bus'],
            ['vga_connector.jpg', 'VGA Connector', 'Carries analogue RGB video and sync signals through a 15-pin D-subminiature connector'],
        ];

        foreach ($items as $index => [$file, $name, $answer]) {
            ImageIdentificationItem::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'item_name' => $name,
                ],
                [
                    'image_path' => 'images/images_identification/' . $file,
                    'description' => $answer,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // Remove items that are no longer in the list.
        ImageIdentificationItem::query()
            ->where('assessment_id', $assessment->id)
            ->whereNotIn('item_name', array_column($items, 1))
            ->delete();
    }
}
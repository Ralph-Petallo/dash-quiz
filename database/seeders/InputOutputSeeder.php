<?php

namespace Database\Seeders;

use App\Models\InputOutput;
use Illuminate\Database\Seeder;

class InputOutputSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            ['name' => 'Keyboard', 'type' => 'input', 'image_key' => 'keyboard'],
            ['name' => 'Mouse', 'type' => 'input', 'image_key' => 'mouse'],
            ['name' => 'Microphone', 'type' => 'input', 'image_key' => 'microphone'],
            ['name' => 'Webcam', 'type' => 'input', 'image_key' => 'webcam'],
            ['name' => 'Scanner', 'type' => 'input', 'image_key' => 'scanner'],
            ['name' => 'Joystick', 'type' => 'input', 'image_key' => 'joystick'],
            ['name' => 'Graphics Tablet', 'type' => 'input', 'image_key' => 'graphics-tablet'],
            ['name' => 'Barcode Scanner', 'type' => 'input', 'image_key' => 'barcode-scanner'],

            ['name' => 'Monitor', 'type' => 'output', 'image_key' => 'monitor'],
            ['name' => 'Speaker', 'type' => 'output', 'image_key' => 'speaker'],
            ['name' => 'Printer', 'type' => 'output', 'image_key' => 'printer'],
            ['name' => 'Headphones', 'type' => 'output', 'image_key' => 'headphones'],
            ['name' => 'Projector', 'type' => 'output', 'image_key' => 'projector'],
            ['name' => 'Plotter', 'type' => 'output', 'image_key' => 'plotter'],
            ['name' => 'LED Display', 'type' => 'output', 'image_key' => 'led-display'],
            ['name' => 'Braille Embosser', 'type' => 'output', 'image_key' => 'braille-embosser'],
        ];

        foreach ($devices as $index => $device) {
            InputOutput::updateOrCreate(
                ['name' => $device['name']],
                [
                    ...$device,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
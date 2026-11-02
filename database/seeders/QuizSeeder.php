<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    public function run()
    {
        // Change this if the quiz has a different ID.
        $quizId = 1;

        // [question, correct answer, wrong, wrong, wrong]
        $items = [
            ['Which component is often called the "brain" of the computer?', 'CPU', 'RAM', 'Hard drive', 'Motherboard'],
            ['Which device is used to store the operating system permanently?', 'Hard drive (or SSD)', 'RAM', 'CPU cache', 'Graphics card'],
            ['Which memory type is volatile and temporarily stores running programs?', 'RAM', 'Hard drive', 'ROM', 'Flash drive'],
            ['Which component supplies power to all parts of the computer?', 'Power supply unit', 'Heat sink', 'Sound card', 'Network card'],
            ['Which component connects all the other parts of the computer together?', 'Motherboard', 'Power supply unit', 'Monitor', 'Keyboard'],
            ['What is the main purpose of a heat sink on a CPU?', 'To remove heat from the processor', 'To increase RAM speed', 'To store data', 'To supply power'],
            ['Which tool is best for removing and tightening screws on a PC case?', 'Screwdriver', 'Hammer', 'Pliers', 'Paintbrush'],
            ['Why should you unplug the computer before opening the case?', 'To prevent electric shock and damage to components', 'To keep the RAM cool', 'To update the BIOS', 'To save power'],
            ['What does ESD stand for?', 'Electrostatic discharge', 'Electronic system drive', 'External storage device', 'Extended security data'],

            ['Which port is commonly used to connect a monitor digitally?', 'HDMI', 'PS/2', 'Serial port', 'Parallel port'],
            ['Which device is an input device?', 'Keyboard', 'Printer', 'Speaker', 'Monitor'],
            ['Which device is an output device?', 'Printer', 'Scanner', 'Mouse', 'Microphone'],
            ['Which device is both an input and an output device?', 'Touchscreen', 'Keyboard', 'Projector', 'Webcam'],
            ['What does BIOS stand for?', 'Basic Input/Output System', 'Binary Input Output Setup', 'Basic Internal Operating System', 'Boot Input Output Service'],
            ['What is the first thing the computer does when powered on?', 'Power-on self-test (POST)', 'Loads the desktop', 'Connects to the internet', 'Opens the browser'],
            ['Which software manages the hardware and programs on a computer?', 'Operating system', 'Antivirus', 'Web browser', 'Spreadsheet'],
            ['Which Windows tool shows devices that have driver problems?', 'Device Manager', 'Task Scheduler', 'Event Viewer', 'Disk Cleanup'],
            ['Which cable is commonly used for a wired home network connection?', 'Ethernet (UTP) cable', 'Coaxial cable for TV', 'Serial cable', 'Audio cable'],
            ['Which device connects a local network to the internet?', 'Router', 'Printer', 'Monitor', 'Keyboard'],
            ['Which device connects multiple computers together in a local network?', 'Switch', 'Speaker', 'Scanner', 'Projector'],
            ['Which service gives a computer its IP address automatically?', 'DHCP', 'DNS', 'FTP', 'SMTP'],
            ['What does IP stand for in IP address?', 'Internet Protocol', 'Internal Port', 'Interface Processor', 'Integrated Program'],
            ['Which command checks whether a computer can reach another device on the network?', 'ping', 'format', 'chkdsk', 'defrag'],

            ['Which Windows feature is used to back up and restore files?', 'File History', 'Disk Defragmenter', 'Task Manager', 'Windows Media Player'],
            ['What is the main purpose of antivirus software?', 'To detect and remove malicious software', 'To speed up the internet', 'To increase RAM', 'To print documents'],
            ['Which password is the most secure?', 'A long password mixing letters, numbers, and symbols', 'Your birthdate', 'A single dictionary word', 'Your name'],
            ['What should you do before installing software from an unknown website?', 'Verify that the source is trusted', 'Disable the firewall', 'Turn off antivirus', 'Install it on the server'],
            ['Which storage unit is the largest?', 'Terabyte (TB)', 'Kilobyte (KB)', 'Megabyte (MB)', 'Gigabyte (GB)'],
            ['How many bits are in one byte?', '8', '4', '16', '32'],
            ['Which processor architecture is used in most desktop PCs?', 'x86 / x64', 'ARM', 'RISC-V', 'PowerPC'],
            ['What does USB stand for?', 'Universal Serial Bus', 'Unified Storage Block', 'Universal System Board', 'Uniform Signal Bridge'],
            ['Which component stores the date and time when the computer is turned off?', 'CMOS battery', 'Power supply', 'Heat sink', 'Cooling fan'],
            ['What is the main function of a graphics card?', 'To process and output images to the display', 'To store files', 'To supply power', 'To connect to the internet'],
            ['Which tool measures voltage in an electrical circuit?', 'Multimeter', 'Torque driver', 'Crimping tool', 'Cable tester'],
            ['What does a yellow triangle with an exclamation mark mean in Device Manager?', 'The device has a problem', 'The device is new', 'The device is turned off', 'The device is updated'],
            ['What is the first step when a computer will not start?', 'Check the power connection and power source', 'Reinstall Windows', 'Replace the RAM', 'Format the hard drive'],

            ['Which file extension is commonly associated with a Windows executable program?', '.exe', '.jpg', '.txt', '.mp3'],
            ['What is the purpose of a file extension?', 'To indicate a file’s type or format', 'To show how much memory is installed', 'To identify the computer’s IP address', 'To determine the file’s physical location'],
            ['Which type of software is used to create and edit spreadsheets?', 'Spreadsheet software', 'Presentation software', 'Web browser software', 'Antivirus software'],
            ['In a spreadsheet, what does a formula usually begin with?', 'An equals sign (=)', 'A question mark (?)', 'A hashtag (#)', 'An ampersand (&)'],
            ['Which type of software is best suited for creating slide-based presentations?', 'Presentation software', 'Database software', 'File compression software', 'Disk management software'],
            ['What is a database?', 'An organized collection of data that can be searched and managed', 'A device used to connect a monitor', 'A program used only to draw pictures', 'A type of computer processor'],
            ['What is the purpose of a primary key in a database table?', 'To uniquely identify each record', 'To format text in a report', 'To connect the database to a monitor', 'To encrypt every file on the computer'],
            ['What does a web browser do?', 'Retrieves and displays web content', 'Assigns IP addresses to network devices', 'Physically connects a computer to a network', 'Supplies power to a computer'],
            ['What is a URL?', 'The address used to locate a resource on the web', 'A type of computer memory', 'A tool for testing electrical voltage', 'A file system used by Windows'],
            ['What is the purpose of a local area network (LAN)?', 'To connect devices within a limited area, such as a school or home', 'To connect only computers located in different countries', 'To store data without using any devices', 'To prevent all network communication'],
            ['What is the purpose of a VPN?', 'To create an encrypted connection over a network', 'To increase a computer’s physical memory', 'To clean dust from computer components', 'To replace a network router'],
            ['What is encryption?', 'Converting information into a form intended to prevent unauthorized reading', 'Deleting all copies of a file', 'Compressing a video to reduce its size', 'Connecting a computer to a printer'],
            ['What is social engineering in cybersecurity?', 'Manipulating people into revealing information or performing unsafe actions', 'Repairing computer hardware using specialized tools', 'Improving a network’s signal strength', 'Organizing files into folders'],
            ['What does file compression generally do?', 'Reduces the amount of space needed to store or transfer files', 'Increases the physical size of a storage device', 'Changes a file into a computer component', 'Prevents a file from being copied'],
        ];

        DB::transaction(function () use ($items, $quizId) {
            $added = 0;

            foreach ($items as $item) {
                $text = $item[0];
                $correct = $item[1];
                $wrong = array_slice($item, 2);

                if (Question::where('quiz_id', $quizId)
                    ->where('question_text', $text)
                    ->exists()) {
                    continue;
                }

                $question = Question::create([
                    'quiz_id' => $quizId,
                    'question_text' => $text,
                ]);

                $options = array_merge(
                    [[$correct, 1]],
                    array_map(fn($answer) => [$answer, 0], $wrong)
                );

                shuffle($options);

                foreach ($options as $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option[0],
                        'is_correct' => $option[1],
                    ]);
                }

                $added++;
            }

            echo "Added {$added} questions\n";
        });
    }
}
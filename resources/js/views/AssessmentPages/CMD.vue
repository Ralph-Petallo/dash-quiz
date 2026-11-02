<template>
    <div class="cmd-terminal">
        <!-- TITLE BAR -->
        <div class="cmd-titlebar">
            <div class="cmd-title">
                <i class="fas fa-terminal"></i>
                Command Prompt
            </div>

            <div class="cmd-controls">
                <span>—</span>
                <span>□</span>
                <span>×</span>
            </div>
        </div>

        <!-- TERMINAL -->
        <div ref="terminalBody" class="cmd-body" @click="focusInput">
            <!-- HISTORY -->
            <div
                v-for="(line, index) in output"
                :key="index"
                class="cmd-output-line"
                :class="line.type"
            >
                <template v-if="line.type === 'command'">
                    <span class="cmd-prompt">
                        {{ line.prompt }}
                    </span>

                    <span class="cmd-command">
                        {{ line.text }}
                    </span>
                </template>

                <template v-else>
                    {{ line.text }}
                </template>
            </div>

            <!-- CURRENT COMMAND -->
            <div class="cmd-input-line">
                <span class="cmd-prompt">
                    {{ prompt }}
                </span>

                <input
                    ref="commandInput"
                    v-model="command"
                    class="cmd-input"
                    type="text"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    @keydown.enter="executeCommand"
                    @keydown.up.prevent="previousCommand"
                    @keydown.down.prevent="nextCommand"
                />

                <span class="cmd-cursor"></span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from "vue";

/* ========================================================
       TERMINAL STATE
    ======================================================== */

const command = ref("");

const commandInput = ref(null);

const terminalBody = ref(null);

const commandHistory = ref([]);

const historyIndex = ref(-1);

/* ========================================================
       FILE SYSTEM
    ======================================================== */

const fileSystem = {
    "C:": {
        type: "directory",

        children: {
            "CSS-PREP": {
                type: "directory",

                children: {
                    Hardware: {
                        type: "directory",
                        children: {
                            "components.txt": {
                                type: "file",
                                size: 824,
                                content:
                                    "Motherboard\r\nCPU\r\nRAM\r\nHDD\r\nSSD\r\nPower Supply Unit\r\n",
                            },
                        },
                    },

                    Software: {
                        type: "directory",
                        children: {
                            "applications.txt": {
                                type: "file",
                                size: 614,
                                content:
                                    "Operating System\r\nDevice Drivers\r\nUtility Software\r\nApplication Software\r\n",
                            },
                        },
                    },

                    Networking: {
                        type: "directory",
                        children: {
                            Routers: {
                                type: "directory",
                                children: {},
                            },

                            Switches: {
                                type: "directory",
                                children: {},
                            },

                            "ipconfig.txt": {
                                type: "file",
                                size: 421,
                                content:
                                    "IPv4 Address: 192.168.1.25\r\nSubnet Mask: 255.255.255.0\r\nDefault Gateway: 192.168.1.1",
                            },

                            "network.txt": {
                                type: "file",
                                size: 912,
                                content:
                                    "Network: CSS-PREP-LAB\r\nStatus: Connected\r\n",
                            },
                        },
                    },

                    Projects: {
                        type: "directory",
                        children: {},
                    },

                    Tools: {
                        type: "directory",
                        children: {},
                    },

                    "README.txt": {
                        type: "file",
                        size: 824,
                        content:
                            "CSS Prep Command Prompt Simulator\r\n\r\nPractice basic Windows command-line operations.",
                    },
                },
            },
        },
    },
};

/* ========================================================
       CURRENT PATH
    ======================================================== */

const currentPath = ref(["C:", "CSS-PREP"]);

/* ========================================================
       PROMPT
    ======================================================== */

const prompt = computed(() => {
    return `${currentPath.value.join("\\")}>`;
});

/* ========================================================
       OUTPUT
    ======================================================== */

const output = ref([
    {
        type: "system",
        text: "Microsoft Windows [Version 10.0.19045.4046]",
    },

    {
        type: "system",
        text: "(c) Microsoft Corporation. All rights reserved.",
    },

    {
        type: "system",
        text: "",
    },

    {
        type: "system",
        text: "CSS Prep Command Prompt Simulator",
    },

    {
        type: "system",
        text: 'Type "help" for a list of available commands.',
    },

    {
        type: "system",
        text: "",
    },
]);

/* ========================================================
       HELP
    ======================================================== */

const commands = {
    help: {
        description: "Display available commands.",
    },

    cls: {
        description: "Clear the screen.",
    },

    dir: {
        description: "List files and folders.",
    },

    cd: {
        description: "Change the current directory.",
    },

    mkdir: {
        description: "Create a new directory.",
    },

    rmdir: {
        description: "Remove an empty directory.",
    },

    tree: {
        description: "Display the folder structure.",
    },

    type: {
        description: "Display the contents of a text file.",
    },

    del: {
        description: "Delete a file.",
    },

    copy: {
        description: "Copy a file.",
    },

    move: {
        description: "Move a file.",
    },

    ren: {
        description: "Rename a file.",
    },

    echo: {
        description: "Display a message.",
    },

    ipconfig: {
        description: "Display network configuration.",
    },

    ping: {
        description: "Test network connectivity.",
    },

    hostname: {
        description: "Display computer name.",
    },

    whoami: {
        description: "Display current user.",
    },

    systeminfo: {
        description: "Display system information.",
    },

    tasklist: {
        description: "Display running processes.",
    },

    exit: {
        description: "Exit the command prompt.",
    },
};

/* ========================================================
       GET DIRECTORY
    ======================================================== */

const getDirectory = (path) => {
    let current = fileSystem;

    for (const part of path) {
        current = current[part];

        if (!current) {
            return null;
        }

        if (current.children) {
            current = current.children;
        }
    }

    return current;
};

/* ========================================================
       CURRENT DIRECTORY OBJECT
    ======================================================== */

const currentDirectory = () => {
    let current = fileSystem;

    for (const part of currentPath.value) {
        if (part === "C:") {
            current = current["C:"];
            continue;
        }

        current = current.children?.[part];
    }

    return current;
};

/* ========================================================
       ADD OUTPUT
    ======================================================== */

const addOutput = (text, type = "output") => {
    output.value.push({
        type,
        text,
    });

    scrollTerminal();
};

/* ========================================================
       COMMAND OUTPUT
    ======================================================== */

const addCommand = (text) => {
    output.value.push({
        type: "command",
        prompt: prompt.value,
        text,
    });

    scrollTerminal();
};

/* ========================================================
       DIR
    ======================================================== */

const commandDir = () => {
    const directory = currentDirectory();

    addOutput(` Directory of ${currentPath.value.join("\\")}`);

    addOutput("");

    if (!directory?.children) {
        return;
    }

    Object.entries(directory.children).forEach(([name, item]) => {
        const date = "09/27/2026";
        const time = "12:30 AM";

        if (item.type === "directory") {
            addOutput(`${date}  ${time}    <DIR>          ${name}`);
        } else {
            addOutput(
                `${date}  ${time}                 ${String(item.size).padStart(8)} ${name}`,
            );
        }
    });

    addOutput("");

    const folders = Object.values(directory.children).filter(
        (item) => item.type === "directory",
    ).length;

    const files = Object.values(directory.children).filter(
        (item) => item.type === "file",
    ).length;

    addOutput(`${files} File(s)`);

    addOutput(`${folders} Dir(s)`);
};

/* ========================================================
       CD
    ======================================================== */

const commandCd = (args) => {
    if (!args) {
        addOutput(currentPath.value.join("\\"));

        return;
    }

    const target = args.trim();

    if (target === "..") {
        if (currentPath.value.length > 1) {
            currentPath.value.pop();
        }

        return;
    }

    if (target === "\\" || target === "/") {
        currentPath.value = ["C:"];

        return;
    }

    const directory = currentDirectory();

    const folder = directory?.children?.[target];

    if (!folder || folder.type !== "directory") {
        addOutput(`The system cannot find the path specified.`);

        return;
    }

    currentPath.value.push(target);
};

/* ========================================================
       MKDIR
    ======================================================== */

const commandMkdir = (args) => {
    if (!args) {
        addOutput("The syntax of the command is incorrect.");

        return;
    }

    const directory = currentDirectory();

    if (directory.children[args]) {
        addOutput(`A subdirectory or file ${args} already exists.`);

        return;
    }

    directory.children[args] = {
        type: "directory",
        children: {},
    };

    addOutput(`Directory "${args}" created.`);
};

/* ========================================================
       RMDIR
    ======================================================== */

const commandRmdir = (args) => {
    if (!args) {
        addOutput("The syntax of the command is incorrect.");

        return;
    }

    const directory = currentDirectory();

    const target = directory.children[args];

    if (!target || target.type !== "directory") {
        addOutput(`The system cannot find the file specified.`);

        return;
    }

    if (Object.keys(target.children).length) {
        addOutput("The directory is not empty.");

        return;
    }

    delete directory.children[args];

    addOutput(`Directory "${args}" removed.`);
};

/* ========================================================
       TYPE
    ======================================================== */

const commandType = (args) => {
    if (!args) {
        addOutput("The syntax of the command is incorrect.");

        return;
    }

    const directory = currentDirectory();

    const file = directory?.children?.[args];

    if (!file || file.type !== "file") {
        addOutput("The system cannot find the file specified.");

        return;
    }

    file.content.split("\r\n").forEach((line) => addOutput(line));
};

/* ========================================================
       DEL
    ======================================================== */

const commandDel = (args) => {
    if (!args) {
        addOutput("The syntax of the command is incorrect.");

        return;
    }

    const directory = currentDirectory();

    const file = directory.children[args];

    if (!file || file.type !== "file") {
        addOutput("The system cannot find the file specified.");

        return;
    }

    delete directory.children[args];

    addOutput(`Deleted ${args}`);
};

/* ========================================================
       ECHO
    ======================================================== */

const commandEcho = (args) => {
    addOutput(args || "");
};

/* ========================================================
       IPCONFIG
    ======================================================== */

const commandIpconfig = () => {
    addOutput("Windows IP Configuration");

    addOutput("");

    addOutput("Ethernet adapter Ethernet:");

    addOutput("");

    addOutput("   Connection-specific DNS Suffix  . :");

    addOutput("   IPv4 Address. . . . . . . . . . . : 192.168.1.25");

    addOutput("   Subnet Mask . . . . . . . . . . . : 255.255.255.0");

    addOutput("   Default Gateway . . . . . . . . . : 192.168.1.1");
};

/* ========================================================
       PING
    ======================================================== */

const commandPing = (args) => {
    if (!args) {
        addOutput("Usage: ping <hostname>");

        return;
    }

    addOutput(`Pinging ${args} with 32 bytes of data:`);

    addOutput("");

    addOutput("Reply from 192.168.1.1: bytes=32 time<1ms TTL=64");

    addOutput("Reply from 192.168.1.1: bytes=32 time<1ms TTL=64");

    addOutput("Reply from 192.168.1.1: bytes=32 time<1ms TTL=64");

    addOutput("Reply from 192.168.1.1: bytes=32 time<1ms TTL=64");

    addOutput("");

    addOutput("Ping statistics for 192.168.1.1:");

    addOutput("    Packets: Sent = 4, Received = 4, Lost = 0 (0% loss)");
};

/* ========================================================
       HOSTNAME
    ======================================================== */

const commandHostname = () => {
    addOutput("CSSPREP-PC");
};

/* ========================================================
       WHOAMI
    ======================================================== */

const commandWhoami = () => {
    addOutput("cssprep\\student");
};

/* ========================================================
       SYSTEMINFO
    ======================================================== */

const commandSystemInfo = () => {
    addOutput("Host Name:                 CSSPREP-PC");

    addOutput("OS Name:                   Microsoft Windows 10");

    addOutput("System Manufacturer:       CSS Prep Virtual Lab");

    addOutput("System Type:               x64-based PC");

    addOutput("Total Physical Memory:     16,384 MB");
};

/* ========================================================
       TASKLIST
    ======================================================== */

const commandTasklist = () => {
    addOutput("Image Name                     PID Session Name");

    addOutput("========================= ======== ================");

    addOutput("System Idle Process              0 Services");

    addOutput("System                           4 Services");

    addOutput("explorer.exe                  2480 Console");

    addOutput("chrome.exe                    5420 Console");

    addOutput("Code.exe                      8124 Console");
};

/* ========================================================
       TREE
    ======================================================== */

const commandTree = () => {
    const directory = currentDirectory();

    addOutput(currentPath.value.join("\\"));

    addOutput("");

    printTree(directory, "");
};

const printTree = (directory, prefix) => {
    if (!directory?.children) {
        return;
    }

    const entries = Object.entries(directory.children);

    entries.forEach(([name, item], index) => {
        const last = index === entries.length - 1;

        addOutput(`${prefix}${last ? "└── " : "├── "}${name}`);

        if (item.type === "directory") {
            printTree(item, `${prefix}${last ? "    " : "│   "}`);
        }
    });
};

/* ========================================================
       CLEAR
    ======================================================== */

const clearTerminal = () => {
    output.value = [];
};

/* ========================================================
       EXECUTE COMMAND
    ======================================================== */

const executeCommand = async () => {
    const raw = command.value.trim();

    if (!raw) {
        return;
    }

    addCommand(raw);

    commandHistory.value.push(raw);

    historyIndex.value = commandHistory.value.length;

    command.value = "";

    const parts = raw.split(/\s+/);

    const commandName = parts.shift().toLowerCase();

    const args = parts.join(" ");

    switch (commandName) {
        case "help":
            Object.entries(commands).forEach(([name, info]) => {
                addOutput(`${name.padEnd(15)} ${info.description}`);
            });
            break;

        case "cls":
            clearTerminal();
            break;

        case "dir":
            commandDir();
            break;

        case "cd":
            commandCd(args);
            break;

        case "mkdir":
        case "md":
            commandMkdir(args);
            break;

        case "rmdir":
        case "rd":
            commandRmdir(args);
            break;

        case "tree":
            commandTree();
            break;

        case "type":
            commandType(args);
            break;

        case "del":
        case "erase":
            commandDel(args);
            break;

        case "echo":
            commandEcho(args);
            break;

        case "ipconfig":
            commandIpconfig();
            break;

        case "ping":
            commandPing(args);
            break;

        case "hostname":
            commandHostname();
            break;

        case "whoami":
            commandWhoami();
            break;

        case "systeminfo":
            commandSystemInfo();
            break;

        case "tasklist":
            commandTasklist();
            break;

        case "exit":
            addOutput("You cannot close this simulator.");
            break;

        default:
            addOutput(
                `'${commandName}' is not recognized as an internal or external command,`,
            );

            addOutput("operable program or batch file.");
    }

    await nextTick();

    scrollTerminal();
};

/* ========================================================
       COMMAND HISTORY
    ======================================================== */

const previousCommand = () => {
    if (!commandHistory.value.length) {
        return;
    }

    if (historyIndex.value > 0) {
        historyIndex.value--;
    }

    command.value = commandHistory.value[historyIndex.value] || "";
};

const nextCommand = () => {
    if (historyIndex.value < commandHistory.value.length - 1) {
        historyIndex.value++;

        command.value = commandHistory.value[historyIndex.value];
    } else {
        historyIndex.value = commandHistory.value.length;

        command.value = "";
    }
};

/* ========================================================
       FOCUS
    ======================================================== */

const focusInput = () => {
    commandInput.value?.focus();
};

/* ========================================================
       SCROLL
    ======================================================== */

const scrollTerminal = () => {
    nextTick(() => {
        if (!terminalBody.value) {
            return;
        }

        terminalBody.value.scrollTop = terminalBody.value.scrollHeight;
    });
};

/* ========================================================
       INITIALIZE
    ======================================================== */

onMounted(() => {
    focusInput();
});
</script>

<style scoped>
.cmd-terminal {
    width: 100%;
    max-width: 950px;
    margin: 30px auto;

    overflow: hidden;

    background: #121212;
    color: #ffffff;

    border: 1px solid #303030;
    border-radius: 6px;

    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.35);

    font-family: "Cascadia Mono", "Consolas", "Courier New", monospace;
}

/* ========================================================
   TITLE BAR
======================================================== */

.cmd-titlebar {
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 12px;

    background: #1f1f1f;

    font-family: "Segoe UI", sans-serif;

    font-size: 13px;
}

.cmd-title {
    display: flex;
    align-items: center;

    gap: 8px;

    color: #eeeeee;
}

.cmd-title i {
    font-size: 12px;
}

.cmd-controls {
    display: flex;
    align-items: center;

    gap: 18px;

    color: #aaaaaa;
}

.cmd-controls span:last-child {
    color: #ffffff;
}

/* ========================================================
   TERMINAL BODY
======================================================== */

.cmd-body {
    min-height: 450px;
    max-height: 650px;

    overflow-y: auto;

    padding: 18px;

    font-size: 14px;
    line-height: 1.55;

    scrollbar-width: thin;
    scrollbar-color: #444 #121212;
}

.cmd-body::-webkit-scrollbar {
    width: 8px;
}

.cmd-body::-webkit-scrollbar-track {
    background: #121212;
}

.cmd-body::-webkit-scrollbar-thumb {
    background: #444;
}

/* ========================================================
   OUTPUT
======================================================== */

.cmd-output-line {
    min-height: 21px;

    white-space: pre-wrap;

    color: #d9d9d9;
}

.cmd-output-line.system {
    color: #ffffff;
}

.cmd-output-line.command {
    color: #ffffff;
}

/* ========================================================
   PROMPT
======================================================== */

.cmd-prompt {
    color: #ffffff;

    white-space: nowrap;
}

.cmd-command {
    margin-left: 7px;

    color: #ffffff;
}

/* ========================================================
   INPUT
======================================================== */

.cmd-input-line {
    display: flex;
    align-items: center;

    min-height: 21px;
}

.cmd-input {
    flex: 1;

    width: 100%;

    margin-left: 7px;

    padding: 0;

    border: 0;
    outline: 0;

    background: transparent;

    color: #ffffff;

    font: inherit;
}

/* ========================================================
   MOBILE
======================================================== */

@media (max-width: 700px) {
    .cmd-terminal {
        margin: 15px 0;

        border-radius: 4px;
    }

    .cmd-body {
        min-height: 400px;

        padding: 14px;

        font-size: 12px;
    }

    .cmd-titlebar {
        height: 34px;
    }
}
</style>

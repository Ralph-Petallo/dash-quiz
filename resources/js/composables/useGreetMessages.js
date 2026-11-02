import { ref, computed } from 'vue'

const messages = ref({
  morning: [
    "Start strong",
    "Rise and grind",
    "Let's get started",
  ],

  afternoon: [
    "Keep the momentum",
    "Keep leveling up",
    "Don't stop now",
    "Keep pushing",
    "Stay on track",
    "Let's keep going"
  ],

  evening: [
    "Finish strong",
    "Keep learning",
    "Keep pushing",
    "You've got this",
  ]
})

const hour = new Date().getHours()

const greetMessage = computed(() => {
  let greetingList

  if (hour < 12) {
    greetingList = messages.value.morning
  } else if (hour < 18) {
    greetingList = messages.value.afternoon
  } else {
    greetingList = messages.value.evening
  }

  return greetingList[Math.floor(Math.random() * greetingList.length)]
})

export function useGreetMessages() {
  return { greetMessage, hour }
}
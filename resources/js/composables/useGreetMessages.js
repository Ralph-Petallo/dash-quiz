import { ref, computed } from 'vue'

const messages = ref({
  morning: "Good Morning",
  afternoon: "Good Afternoon",
  evening: "Good Evening"
})

const hour = new Date().getHours()

const greetMessage = computed(() => {
  if (hour < 12) {
    return messages.value.morning
  } else if (hour < 18) {
    return messages.value.afternoon
  }
  return messages.value.evening
})


export function useGreetMessages() {
  return { greetMessage, hour }
}
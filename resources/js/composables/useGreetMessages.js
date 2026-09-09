import { ref, computed } from 'vue'

const messages = ref({
  morning: [
    "Good Morning!",
    "Rise and Shine!",
    "Ready to Learn?",
    "Start Strong!",
    "A New Day, A New Challenge!",
    "Let's Get Started!",
    "Ready to Level Up?",
    "Time to Sharpen Your Skills!",
    "Make Today Count!",
    "Let's Begin!",
    "Ready for a Fresh Start?",
    "Your Next Challenge Awaits!",
    "Start Your Day Strong!",
    "Time to Power Up!",
    "Let's Make Some Progress!"
  ],

  afternoon: [
    "Good Afternoon!",
    "Keep Going!",
    "Keep the Momentum!",
    "Ready for Another Challenge?",
    "Keep Leveling Up!",
    "Time to Practice!",
    "Stay on Track!",
    "Keep Building Your Skills!",
    "You're Doing Great!",
    "Don't Stop Now!",
    "Keep Pushing Forward!",
    "Time to Test Your Skills!",
    "Make This Session Count!",
    "Ready for the Next Challenge?",
    "Keep That Progress Going!"
  ],

  evening: [
    "Good Evening!",
    "Finish Strong!",
    "Time to Level Up!",
    "Keep Learning!",
    "One More Challenge?",
    "End the Day Strong!",
    "Time to Master Your Skills!",
    "Keep Pushing!",
    "You've Got This!",
    "Ready for One Last Challenge?",
    "Keep the Progress Going!",
    "Time to Sharpen Your Skills!",
    "Another Step Toward Mastery!",
    "Make Your Last Session Count!",
    "End Your Day on a High Note!"
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
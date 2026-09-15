import { ref, computed } from 'vue'

const messages = ref({
  morning: [
    "Ready to Level Up?",
    "Start Strong!",
    "Rise and Grind!",
    "Ready for a Challenge?",
    "Let's Get Started!",
    "Time to Power Up!",
    "New Day, New Knowledge!",
    "Your Next Challenge Awaits!",
    "Let's Make Some Progress!",
    "Ready to Test Yourself?"
  ],

  afternoon: [
    "Keep the Momentum!",
    "Keep Leveling Up!",
    "Ready for the Next Challenge?",
    "Don't Stop Now!",
    "Keep Pushing!",
    "Time to Test Your Skills!",
    "You're Making Progress!",
    "Stay on Track!",
    "Keep Building Your Skills!",
    "Let's Keep Going!"
  ],

  evening: [
    "Finish Strong!",
    "One More Challenge?",
    "Time to Level Up!",
    "Keep Learning!",
    "End the Day Strong!",
    "Ready for One Last Challenge?",
    "Another Step Toward Mastery!",
    "Keep Pushing!",
    "You've Got This!",
    "Make Your Last Session Count!"
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
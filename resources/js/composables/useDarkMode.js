import { ref, watch, onMounted } from 'vue'

// Create global dark mode state
const darkModeEnabled = ref(false)

export function useDarkMode() {
  // Initialize dark mode from localStorage or system preference
  const initializeDarkMode = () => {
    const stored = localStorage.getItem('dash-quiz-darkmode')
    
    if (stored !== null) {
      // User has a preference saved
      darkModeEnabled.value = stored === 'true'
    } else {
      // Check system preference
      darkModeEnabled.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    
    // Apply dark mode to document
    applyDarkMode()
  }

  // Apply dark mode to document root
  const applyDarkMode = () => {
    if (darkModeEnabled.value) {
      document.documentElement.classList.add('dark-mode')
    } else {
      document.documentElement.classList.remove('dark-mode')
    }
  }

  // Toggle dark mode
  const toggleDarkMode = () => {
    darkModeEnabled.value = !darkModeEnabled.value
    localStorage.setItem('dash-quiz-darkmode', String(darkModeEnabled.value))
    applyDarkMode()
  }

  // Set dark mode explicitly
  const setDarkMode = (enabled) => {
    darkModeEnabled.value = enabled
    localStorage.setItem('dash-quiz-darkmode', String(enabled))
    applyDarkMode()
  }

  // Watch for changes in system preference
  onMounted(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    const handleChange = (e) => {
      // Only apply system preference if user hasn't set a preference
      if (localStorage.getItem('dash-quiz-darkmode') === null) {
        darkModeEnabled.value = e.matches
        applyDarkMode()
      }
    }

    mediaQuery.addEventListener('change', handleChange)

    return () => {
      mediaQuery.removeEventListener('change', handleChange)
    }
  })

  return {
    darkModeEnabled,
    initializeDarkMode,
    toggleDarkMode,
    setDarkMode
  }
}

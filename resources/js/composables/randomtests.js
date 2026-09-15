import { ref, readonly } from 'vue'


export default () => {
    const count = ref(0)
    const increment = () => count.value++

    return {
        count: readonly(count),
        increment
    }
}
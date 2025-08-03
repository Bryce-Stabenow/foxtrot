<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="transform translate-y-2 opacity-0"
    enter-to-class="transform translate-y-0 opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="transform translate-y-0 opacity-100"
    leave-to-class="transform translate-y-2 opacity-0"
  >
    <div
      v-if="modelValue"
      class="fixed bottom-4 right-4 z-50 max-w-sm w-full"
    >
      <div
        :class="[
          'border rounded-lg p-4 shadow-lg flex items-start space-x-3',
          getColorClasses()
        ]"
      >
        <Icon
          :name="getIcon()"
          :class="`h-5 w-5 mt-0.5 flex-shrink-0 ${getIconColor()}`"
        />
        <div class="flex-1">
          <p class="text-sm font-medium">{{ message }}</p>
        </div>
        <button
          @click="closeToast"
          class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600"
        >
          <Icon name="X" class="h-4 w-4" />
        </button>
      </div>
    </div>
  </Transition>
</template> 

<script setup lang="ts">
import { watch } from 'vue'
import Icon from '@/components/Icon.vue'

interface ToastProps {
  message: string
  type?: 'success' | 'error' | 'info'
  duration?: number
  modelValue: boolean
}

const props = withDefaults(defineProps<ToastProps>(), {
  type: 'success',
  duration: 3000,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Watch for when the toast becomes visible and start the timer
watch(() => props.modelValue, (newValue) => {
  if (newValue && props.duration > 0) {
    setTimeout(() => {
      emit('update:modelValue', false)
    }, props.duration)
  }
})

const closeToast = () => {
  emit('update:modelValue', false)
}

const getIcon = () => {
  switch (props.type) {
    case 'success':
      return 'CheckCircle'
    case 'error':
      return 'XCircle'
    case 'info':
      return 'Info'
    default:
      return 'CheckCircle'
  }
}

const getColorClasses = () => {
  switch (props.type) {
    case 'success':
      return 'bg-green-50 border-green-200 text-green-800'
    case 'error':
      return 'bg-red-50 border-red-200 text-red-800'
    case 'info':
      return 'bg-blue-50 border-blue-200 text-blue-800'
    default:
      return 'bg-green-50 border-green-200 text-green-800'
  }
}

const getIconColor = () => {
  switch (props.type) {
    case 'success':
      return 'text-green-600'
    case 'error':
      return 'text-red-600'
    case 'info':
      return 'text-blue-600'
    default:
      return 'text-green-600'
  }
}
</script>
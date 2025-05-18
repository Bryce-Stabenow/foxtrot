<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 42" :class="className" v-bind="$attrs">
        <path stroke="none" d="M0 0h24v24H0z" fill="currentColor" />
        <path d="M7 20v-16h5.5a4 4 0 0 1 0 9h-5.5" fill="currentColor" />
    </svg>
</template>

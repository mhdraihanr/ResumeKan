<script setup lang="ts">
// Heading section CV: token beda per template via props.
// modern: navy 2px, 10pt · classic: slate-900 1.5px, 11pt
defineProps<{ title: string; modern?: boolean }>();
</script>

<template>
  <h2
    :class="[
      'pb-1 font-bold uppercase tracking-widest text-slate-900',
      modern
        ? 'border-b-2 border-[#1e40af] text-[10pt]'
        : 'border-b-[1.5px] border-slate-900 text-[11pt]',
    ]"
  >
    {{ title }}
  </h2>
</template>

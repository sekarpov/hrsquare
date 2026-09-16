<script setup lang="ts">
// Adapted from Inspira UI's copy-and-paste BorderBeam component.
// Source: https://inspira-ui.com/r/border-beam.json
// CSS replaces its Tailwind class helper; no runtime animation dependency is needed.
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        size?: number;
        duration?: number;
        borderWidth?: number;
        offset?: number;
        colorFrom?: string;
        colorTo?: string;
    }>(),
    {
        size: 140,
        duration: 3.6,
        borderWidth: 2.5,
        offset: 2,
        colorFrom: "var(--nine-box-on-blue)",
        colorTo: "var(--nine-box-foreground)",
    },
);
const beamStyle = computed(() => ({
    "--beam-size": `${props.size}px`,
    "--beam-duration": `${props.duration}s`,
    "--beam-width": `${props.borderWidth}px`,
    "--beam-inset": `${-props.offset}px`,
    "--beam-from": props.colorFrom,
    "--beam-to": props.colorTo,
}));
</script>

<template>
    <span class="border-beam" :style="beamStyle" aria-hidden="true" />
</template>

<style scoped>
.border-beam {
    position: absolute;
    inset: var(--beam-inset);
    border-radius: inherit;
    border: var(--beam-width) solid transparent;
    pointer-events: none;
    mask:
        linear-gradient(white, white) padding-box,
        linear-gradient(white, white);
    mask-composite: exclude;
}
.border-beam::after {
    content: "";
    position: absolute;
    width: var(--beam-size);
    aspect-ratio: 1;
    background: linear-gradient(
        to left,
        var(--beam-from),
        var(--beam-to),
        transparent
    );
    offset-anchor: 90% 50%;
    offset-path: rect(0 auto auto 0 round 8px);
    animation: border-beam-travel var(--beam-duration) linear infinite;
}
@keyframes border-beam-travel {
    to {
        offset-distance: 100%;
    }
}
@media (prefers-reduced-motion: reduce) {
    .border-beam {
        display: none;
    }
    .border-beam::after {
        animation: none;
    }
}
</style>

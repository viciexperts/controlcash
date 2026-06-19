<script setup>
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import { t } from '@/i18n';

const isVisible = ref(false);
let pendingVisits = 0;

const shouldBlockVisit = (event) => {
    const method = event.detail.visit.method?.toLowerCase() || 'get';

    return method !== 'get';
};

const stopStartListener = router.on('start', (event) => {
    if (! shouldBlockVisit(event)) {
        return;
    }

    pendingVisits += 1;
    isVisible.value = true;
});

const stopFinishListener = router.on('finish', (event) => {
    if (! shouldBlockVisit(event)) {
        return;
    }

    pendingVisits = Math.max(pendingVisits - 1, 0);
    isVisible.value = pendingVisits > 0;
});

onBeforeUnmount(() => {
    stopStartListener();
    stopFinishListener();
});
</script>

<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <section
                v-if="isVisible"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/75 px-6 text-white backdrop-blur-sm"
                role="alert"
                aria-live="assertive"
                aria-busy="true"
            >
                <div class="flex flex-col items-center rounded-lg bg-white px-8 py-7 text-center text-slate-950 shadow-2xl dark:bg-slate-900 dark:text-white">
                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></div>
                    <p class="mt-5 text-lg font-black">{{ t('status.processing') }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('status.processing_detail') }}</p>
                </div>
            </section>
        </transition>
    </Teleport>
</template>

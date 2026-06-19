<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { motivationalQuotes } from '@/Data/motivationalQuotes';

const page = usePage();
const quote = ref('');
const isVisible = ref(false);
let timeoutId;

const storageKey = computed(() => `controlcash:motivational-quote-shown:${page.props.auth.user.id}`);

const close = () => {
    isVisible.value = false;

    if (timeoutId) {
        clearTimeout(timeoutId);
    }
};

onMounted(() => {
    if (typeof window === 'undefined' || sessionStorage.getItem(storageKey.value)) {
        return;
    }

    quote.value = motivationalQuotes[Math.floor(Math.random() * motivationalQuotes.length)];
    sessionStorage.setItem(storageKey.value, 'true');
    isVisible.value = true;
    timeoutId = window.setTimeout(close, 5000);
});

onBeforeUnmount(() => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});
</script>

<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <section
                v-if="isVisible"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950 px-6 text-white"
                role="dialog"
                aria-modal="true"
                aria-label="Frase motivacional"
            >
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.28),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(34,197,94,0.16),_transparent_34%)]"></div>

                <button
                    type="button"
                    class="absolute right-5 top-5 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-lg font-black text-white transition hover:bg-white/20"
                    aria-label="Cerrar frase"
                    @click="close"
                >
                    X
                </button>

                <div class="relative mx-auto max-w-4xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.28em] text-emerald-300">ControlCash</p>
                    <blockquote class="mt-8 text-4xl font-black leading-tight tracking-tight sm:text-6xl">
                        “{{ quote }}”
                    </blockquote>
                    <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">
                        Esta pantalla se cerrara automaticamente
                    </p>
                </div>
            </section>
        </transition>
    </Teleport>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ groups: Array });

const form = useForm({
    name: '',
    description: '',
});

const submit = () => {
    form.post(route('groups.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Grupos" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Grupos</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <form @submit.prevent="submit" class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Nuevo grupo</h2>
                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Nombre</span>
                            <input v-model="form.name" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        </label>
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Descripcion</span>
                            <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                        </label>
                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Crear grupo
                        </button>
                    </div>
                </form>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tus grupos</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <Link
                            v-for="group in groups"
                            :key="group.id"
                            :href="route('groups.show', group.id)"
                            class="rounded-lg border border-slate-200 p-4 transition hover:border-emerald-500 dark:border-slate-800"
                        >
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ group.name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ group.description || 'Sin descripcion' }}</p>
                            <div class="mt-4 flex gap-3 text-xs text-slate-500">
                                <span>{{ group.members_count }} miembros</span>
                                <span>{{ group.expenses_count }} gastos</span>
                            </div>
                        </Link>
                    </div>
                    <p v-if="!groups.length" class="mt-4 text-sm text-slate-500">
                        Crea un grupo para compartir gastos con familia, amigos o roommates.
                    </p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

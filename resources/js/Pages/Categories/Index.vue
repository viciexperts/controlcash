<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({ categories: Array });

const form = useForm({
    name: '',
    color: '#2563eb',
    icon: 'tag',
});

const submit = () => {
    form.post(route('categories.store'), {
        onSuccess: () => form.reset('name', 'icon'),
    });
};

const update = (category) => {
    router.put(route('categories.update', category.id), category, {
        preserveScroll: true,
    });
};

const archive = (category) => {
    router.delete(route('categories.destroy', category.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Categorias" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Categorias</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <form @submit.prevent="submit" class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Nueva categoria</h2>
                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Nombre</span>
                            <input v-model="form.name" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        </label>
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Color</span>
                            <input v-model="form.color" type="color" class="mt-1 h-10 w-full rounded-md border-slate-300 dark:border-slate-700" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Icono</span>
                            <input v-model="form.icon" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="tag, book, shopping-bag" required />
                        </label>
                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Guardar categoria
                        </button>
                    </div>
                </form>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tus categorias</h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="grid gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800 md:grid-cols-5"
                            :class="{ 'opacity-50': !category.is_active }"
                        >
                            <input v-model="category.name" class="rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white md:col-span-2" />
                            <input v-model="category.color" type="color" class="h-10 rounded-md border-slate-300 dark:border-slate-700" />
                            <input v-model="category.icon" class="rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                            <div class="flex gap-2">
                                <button @click="update(category)" type="button" class="flex-1 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                                    Actualizar
                                </button>
                                <button @click="archive(category)" type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                    Archivar
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

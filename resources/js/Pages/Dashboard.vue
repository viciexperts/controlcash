<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    summary: Object,
    byCategory: Array,
    recentExpenses: Array,
});

const money = (value) =>
    new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP',
    }).format(value || 0);
</script>

<template>
    <Head title="Resumen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Resumen</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Vista rapida de tus gastos personales y compartidos.
                    </p>
                </div>
                <Link
                    :href="route('expenses.index')"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    Agregar gasto
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <p class="text-sm text-slate-500">Gastado hoy</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ money(summary.today) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <p class="text-sm text-slate-500">Este mes</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ money(summary.month) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <p class="text-sm text-slate-500">Gastos de grupo</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ money(summary.group_month) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <p class="text-sm text-slate-500">Gastos personales</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ money(summary.personal_month) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-1">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Por categoria</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="category in byCategory" :key="category.name">
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-300">{{ category.name }}</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ money(category.amount) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800">
                                <div
                                    class="h-2 rounded-full"
                                    :style="{
                                        width: `${Math.min((category.amount / Math.max(summary.month, 1)) * 100, 100)}%`,
                                        backgroundColor: category.color,
                                    }"
                                />
                            </div>
                        </div>
                        <p v-if="!byCategory.length" class="text-sm text-slate-500">
                            Aun no hay gastos este mes.
                        </p>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ultimos gastos</h2>
                        <Link :href="route('expenses.index')" class="text-sm font-medium text-emerald-600">
                            Ver todos
                        </Link>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                        <div
                            v-for="expense in recentExpenses"
                            :key="expense.id"
                            class="grid gap-2 border-b border-slate-100 px-4 py-3 last:border-0 dark:border-slate-800 sm:grid-cols-4"
                        >
                            <div class="sm:col-span-2">
                                <p class="font-medium text-slate-900 dark:text-white">{{ expense.description }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ expense.category || 'Sin categoria' }}
                                    <span v-if="expense.group"> · {{ expense.group }}</span>
                                </p>
                            </div>
                            <p class="text-sm text-slate-500">{{ expense.expense_date }}</p>
                            <p class="font-semibold text-slate-900 dark:text-white sm:text-right">
                                {{ money(expense.amount) }}
                            </p>
                        </div>
                        <p v-if="!recentExpenses.length" class="p-4 text-sm text-slate-500">
                            Crea tu primer gasto para empezar a ver el resumen.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    expenses: Array,
    categories: Array,
    groups: Array,
});

const page = usePage();
const today = new Date().toISOString().slice(0, 10);
const form = useForm({
    description: '',
    amount: '',
    expense_date: today,
    category_id: '',
    group_id: '',
    paid_by_user_id: '',
    split_type: 'equal',
    participant_ids: [],
    splits: [],
    notes: '',
});

const selectedGroup = computed(() =>
    props.groups.find((group) => Number(group.id) === Number(form.group_id)),
);
const hasGroups = computed(() => props.groups.length > 0);

const selectAllGroupMembers = (group) => {
    form.participant_ids = group?.members?.map((member) => member.id) || [];
    form.paid_by_user_id =
        group?.members?.find((member) => Number(member.id) === Number(page.props.auth.user.id))?.id ||
        group?.members?.[0]?.id ||
        '';
};

watch(selectedGroup, (group) => {
    if (group) {
        selectAllGroupMembers(group);
        return;
    }

    form.paid_by_user_id = '';
    form.participant_ids = [];
});

const money = (value) =>
    new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP',
    }).format(value || 0);

const submit = () => {
    if (!form.group_id) {
        form.paid_by_user_id = '';
        form.participant_ids = [];
    } else if (selectedGroup.value && !form.participant_ids.length) {
        selectAllGroupMembers(selectedGroup.value);
    }

    form.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('description', 'amount', 'notes', 'group_id', 'paid_by_user_id', 'participant_ids');
            form.expense_date = today;
            form.split_type = 'equal';
        },
    });
};

const remove = (expense) => {
    router.delete(route('expenses.destroy', expense.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Gastos" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gastos</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <form @submit.prevent="submit" class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Agregar gasto</h2>
                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Descripcion</span>
                            <input v-model="form.description" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm text-slate-600 dark:text-slate-300">Monto</span>
                                <input v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                            </label>
                            <label class="block">
                                <span class="text-sm text-slate-600 dark:text-slate-300">Fecha</span>
                                <input v-model="form.expense_date" type="date" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Categoria</span>
                            <select v-model="form.category_id" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                <option value="">Sin categoria</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
                        </label>
                        <label v-if="hasGroups" class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Grupo</span>
                            <select v-model="form.group_id" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                <option value="">Personal</option>
                                <option v-for="group in groups" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">
                                Si eliges un grupo, todos sus miembros podran ver el gasto.
                            </p>
                            <p v-if="form.errors.group_id" class="mt-1 text-xs text-red-600">{{ form.errors.group_id }}</p>
                        </label>
                        <div v-else class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                            Este gasto sera personal. Para compartir gastos, primero
                            <Link :href="route('groups.index')" class="font-semibold underline">crea un grupo</Link>.
                        </div>

                        <div v-if="selectedGroup" class="space-y-4 rounded-lg bg-slate-50 p-4 dark:bg-slate-950">
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                Todos los miembros de {{ selectedGroup.name }} podran ver este gasto en su historial y en el detalle del grupo.
                            </p>
                            <label class="block">
                                <span class="text-sm text-slate-600 dark:text-slate-300">Pagado por</span>
                                <select v-model="form.paid_by_user_id" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required>
                                    <option value="">Seleccionar</option>
                                    <option v-for="member in selectedGroup.members" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.paid_by_user_id" class="mt-1 text-xs text-red-600">{{ form.errors.paid_by_user_id }}</p>
                            </label>
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Participantes</p>
                                    <button type="button" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300" @click="selectAllGroupMembers(selectedGroup)">
                                        Seleccionar todos
                                    </button>
                                </div>
                                <label v-for="member in selectedGroup.members" :key="member.id" class="mt-2 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                    <input v-model="form.participant_ids" type="checkbox" :value="member.id" class="rounded border-slate-300 text-emerald-600" />
                                    {{ member.name }}
                                </label>
                                <p class="mt-2 text-xs text-slate-500">
                                    Si no marcas participantes, se divide entre todos.
                                </p>
                                <p v-if="form.errors.participant_ids" class="mt-1 text-xs text-red-600">{{ form.errors.participant_ids }}</p>
                            </div>
                        </div>

                        <label class="block">
                            <span class="text-sm text-slate-600 dark:text-slate-300">Notas</span>
                            <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
                        </label>
                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Guardar gasto
                        </button>
                    </div>
                </form>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Historial</h2>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                        <div
                            v-for="expense in expenses"
                            :key="expense.id"
                            class="grid gap-3 border-b border-slate-100 p-4 last:border-0 dark:border-slate-800 md:grid-cols-5"
                        >
                            <div class="md:col-span-2">
                                <p class="font-medium text-slate-900 dark:text-white">{{ expense.description }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ expense.category?.name || 'Sin categoria' }}
                                    <span v-if="expense.group"> · {{ expense.group.name }}</span>
                                </p>
                                <p v-if="expense.payer" class="text-xs text-slate-500">
                                    Pago: {{ expense.payer.name }}
                                </p>
                            </div>
                            <p class="text-sm text-slate-500">{{ expense.expense_date }}</p>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ money(expense.amount) }}</p>
                            <div class="text-right">
                                <button @click="remove(expense)" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                        <p v-if="!expenses.length" class="p-4 text-sm text-slate-500">
                            Aun no hay gastos registrados.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    balances: Object,
    isAdmin: Boolean,
    isCreator: Boolean,
    users: Array,
});

const memberForm = useForm({ email: '' });
const settlementForm = useForm({
    from_user_id: '',
    to_user_id: '',
    amount: '',
    settled_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

const money = (value) =>
    new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP',
    }).format(value || 0);

const addMember = () => {
    memberForm.post(route('groups.members.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
};

const removeMember = (member) => {
    router.delete(route('groups.members.destroy', [props.group.id, member.id]), {
        preserveScroll: true,
    });
};

const removeGroup = () => {
    router.delete(route('groups.destroy', props.group.id));
};

const settle = () => {
    settlementForm.post(route('groups.settlements.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => settlementForm.reset('from_user_id', 'to_user_id', 'amount', 'notes'),
    });
};

const approveExpense = (expense) => {
    router.post(route('expenses.approve', expense.id), {}, {
        preserveScroll: true,
    });
};

const statusLabel = (status) => ({
    approved: 'Aprobado',
    pending: 'Pendiente',
    rejected: 'Rechazado',
}[status] || status);

const statusClass = (status) => ({
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
}[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300');
</script>

<template>
    <Head :title="group.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ group.name }}</h1>
                    <p class="text-sm text-slate-500">{{ group.description || 'Gastos compartidos del grupo.' }}</p>
                </div>
                <button v-if="isCreator" @click="removeGroup" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950">
                    Borrar grupo
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Miembros</h2>
                    <form @submit.prevent="addMember" class="mt-4 flex gap-2">
                        <input v-model="memberForm.email" type="email" placeholder="correo@ejemplo.com" class="min-w-0 flex-1 rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        <button class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Agregar</button>
                    </form>
                    <div class="mt-4 space-y-3">
                        <div v-for="member in group.members" :key="member.id" class="flex items-center justify-between rounded-md bg-slate-50 p-3 dark:bg-slate-950">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ member.name }}</p>
                                <p class="text-xs text-slate-500">{{ member.email }}</p>
                            </div>
                            <button @click="removeMember(member)" class="text-xs text-slate-500 hover:text-red-600">Quitar</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Balance</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div v-for="item in balances.summary" :key="item.user.id" class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <p class="font-medium text-slate-900 dark:text-white">{{ item.user.name }}</p>
                            <p
                                class="mt-2 text-xl font-bold"
                                :class="item.balance >= 0 ? 'text-emerald-600' : 'text-red-600'"
                            >
                                {{ money(item.balance) }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ item.balance >= 0 ? 'Le deben' : 'Debe' }}
                            </p>
                        </div>
                    </div>

                    <h3 class="mt-6 font-semibold text-slate-900 dark:text-white">Sugerencias para saldar</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="settlement in balances.suggested_settlements" :key="`${settlement.from.id}-${settlement.to.id}-${settlement.amount}`" class="rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-950 dark:text-slate-200">
                            {{ settlement.from.name }} debe pagar {{ money(settlement.amount) }} a {{ settlement.to.name }}.
                        </div>
                        <p v-if="!balances.suggested_settlements.length" class="text-sm text-slate-500">
                            No hay deudas pendientes.
                        </p>
                    </div>
                </section>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <form @submit.prevent="settle" class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Registrar pago</h2>
                    <div class="mt-4 space-y-4">
                        <select v-model="settlementForm.from_user_id" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required>
                            <option value="">Quien paga</option>
                            <option v-for="member in group.members" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                        <select v-model="settlementForm.to_user_id" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required>
                            <option value="">Quien recibe</option>
                            <option v-for="member in group.members" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                        <input v-model="settlementForm.amount" type="number" min="0.01" step="0.01" placeholder="Monto" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        <input v-model="settlementForm.settled_at" type="date" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required />
                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Saldar</button>
                    </div>
                </form>

                <section class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Gastos del grupo</h2>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                        <div v-for="expense in group.expenses" :key="expense.id" class="grid gap-2 border-b border-slate-100 p-4 last:border-0 dark:border-slate-800 sm:grid-cols-5">
                            <div class="sm:col-span-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ expense.description }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusClass(expense.approval_status)">
                                        {{ statusLabel(expense.approval_status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500">{{ expense.category?.name || 'Sin categoria' }}</p>
                                <a v-if="expense.receipt_path" :href="`/storage/${expense.receipt_path}`" target="_blank" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    Ver recibo
                                </a>
                            </div>
                            <p class="text-sm text-slate-500">Pago {{ expense.payer?.name }}</p>
                            <p class="font-semibold text-slate-900 dark:text-white sm:text-right">{{ money(expense.amount) }}</p>
                            <div class="text-right">
                                <button v-if="isAdmin && expense.approval_status === 'pending'" @click="approveExpense(expense)" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">
                                    Aprobar
                                </button>
                            </div>
                        </div>
                        <p v-if="!group.expenses.length" class="p-4 text-sm text-slate-500">
                            Agrega gastos desde la pantalla de gastos seleccionando este grupo.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

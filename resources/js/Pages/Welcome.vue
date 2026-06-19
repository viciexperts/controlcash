<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="ControlCash" />

    <main class="min-h-screen bg-slate-950 text-white">
        <section class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-12">
            <div class="max-w-3xl">
                <div class="mb-6 inline-flex rounded-lg bg-emerald-500 px-3 py-1 text-sm font-bold text-slate-950">
                    MVP Laravel + Vue + SQLite
                </div>
                <h1 class="text-4xl font-black tracking-tight sm:text-6xl">ControlCash</h1>
                <p class="mt-5 text-lg leading-8 text-slate-300">
                    Registra gastos personales, crea grupos, comparte cuentas, asigna quien pago y mira quien debe a quien.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link :href="route('login')" class="rounded-md bg-emerald-500 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-emerald-400">
                        Entrar
                    </Link>
                    <Link :href="route('register')" class="rounded-md border border-slate-700 px-5 py-3 text-sm font-bold text-white hover:bg-slate-900">
                        Crear cuenta
                    </Link>
                </div>
            </div>
        </section>
    </main>
</template>

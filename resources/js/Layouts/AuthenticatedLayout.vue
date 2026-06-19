<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import MotivationalQuoteOverlay from '@/Components/MotivationalQuoteOverlay.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { t } from '@/i18n';

const showingNavigationDropdown = ref(false);

const links = [
    ['dashboard', 'nav.dashboard', 'dashboard'],
    ['expenses.index', 'nav.expenses', 'expenses.*'],
    ['groups.index', 'nav.groups', 'groups.*'],
    ['categories.index', 'nav.categories', 'categories.*'],
];
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
        <MotivationalQuoteOverlay />

        <nav class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-3">
                                <ApplicationLogo />
                                <span class="hidden text-sm font-bold text-slate-900 dark:text-white sm:block">
                                    {{ t('app.name') }}
                                </span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                v-for="[href, label, active] in links"
                                :key="href"
                                :href="route(href)"
                                :active="route().current(active)"
                            >
                                {{ t(label) }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <span class="ms-2">⌄</span>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    {{ t('nav.profile') }}
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    {{ t('nav.logout') }}
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            ☰
                        </button>
                    </div>
                </div>
            </div>

            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        v-for="[href, label, active] in links"
                        :key="href"
                        :href="route(href)"
                        :active="route().current(active)"
                    >
                        {{ t(label) }}
                    </ResponsiveNavLink>
                </div>

                <div class="border-t border-slate-200 pb-1 pt-4 dark:border-slate-700">
                    <div class="px-4">
                        <div class="text-base font-medium text-slate-800 dark:text-slate-100">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm font-medium text-slate-500">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            {{ t('nav.profile') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            {{ t('nav.logout') }}
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow-sm dark:bg-slate-900" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>

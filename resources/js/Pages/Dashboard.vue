<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { currency } from '@/composables/useCurrency';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard — Sistema de ventas e inventario de vinos
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link :href="route('products.index')" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <div class="text-sm text-gray-500">Productos</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.products }}</div>
                        <div class="mt-1 text-xs text-indigo-600">Ver inventario →</div>
                    </Link>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm text-gray-500">Unidades en stock</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.totalStock }}</div>
                        <div class="mt-1 text-xs font-medium text-amber-600">{{ stats.lowStock }} producto(s) con stock bajo (≤ 10)</div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm text-gray-500">Valor del inventario</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ currency(stats.stockValue) }}</div>
                    </div>

                    <Link :href="route('sales.index')" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <div class="text-sm text-gray-500">Ventas registradas</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.sales }}</div>
                        <div class="mt-1 text-xs text-indigo-600">Ver ventas →</div>
                    </Link>
                </div>

                <!-- Accesos rápidos -->
                <div class="mt-8 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-700">Accesos rápidos</h3>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <Link :href="route('sales.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                            + Registrar venta
                        </Link>
                        <Link :href="route('products.create')" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                            + Nuevo producto
                        </Link>
                        <Link :href="route('kardex.index')" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                            Ver kardex
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

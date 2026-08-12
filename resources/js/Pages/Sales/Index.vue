<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

defineProps({
    sales: { type: Object, required: true },
});

const currency = (value) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(Number(value) || 0);
const dateFmt = (value) => new Date(value).toLocaleString('es-CL');
</script>

<template>
    <Head title="Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Ventas</h2>
                <Link
                    :href="route('sales.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500"
                >
                    + Nueva venta
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">N° Venta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cliente</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Ítems</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Subtotal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="sale in sales.data" :key="sale.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ sale.sale_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ sale.customer_name || 'Cliente general' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">{{ sale.total_items }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">{{ currency(sale.subtotal) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">{{ currency(sale.total) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">{{ dateFmt(sale.created_at) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('sales.show', sale.id)" class="text-indigo-600 hover:text-indigo-900">Ver</Link>
                                </td>
                            </tr>
                            <tr v-if="sales.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Aún no hay ventas registradas.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4" v-if="sales.last_page > 1">
                        <Link :href="sales.prev_page_url || '#'" class="text-sm text-gray-600 hover:text-gray-900" :class="{ 'pointer-events-none opacity-40': !sales.prev_page_url }">
                            ← Anterior
                        </Link>
                        <span class="text-sm text-gray-500">Página {{ sales.current_page }} de {{ sales.last_page }}</span>
                        <Link :href="sales.next_page_url || '#'" class="text-sm text-gray-600 hover:text-gray-900" :class="{ 'pointer-events-none opacity-40': !sales.next_page_url }">
                            Siguiente →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
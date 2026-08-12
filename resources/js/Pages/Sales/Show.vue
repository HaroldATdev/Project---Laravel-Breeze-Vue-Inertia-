<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

defineProps({
    sale: { type: Object, required: true },
});

const currency = (value) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(Number(value) || 0);
const dateFmt = (value) => new Date(value).toLocaleString('es-CL');
</script>

<template>
    <Head :title="`Venta ${sale.sale_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Venta {{ sale.sale_number }}</h2>
                <Link :href="route('sales.index')" class="text-sm text-gray-600 hover:text-gray-900">← Volver a ventas</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Cliente</div>
                                <div class="font-medium text-gray-900">{{ sale.customer_name || 'Cliente general' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-500">Fecha</div>
                                <div class="font-medium text-gray-900">{{ dateFmt(sale.created_at) }}</div>
                            </div>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Producto</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Precio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cantidad</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="item in sale.items" :key="item.id">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ item.product_name }}
                                    <span v-if="item.product" class="block text-xs text-gray-500">{{ item.product.brand }} · {{ item.product.presentation }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ currency(item.unit_price) }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ currency(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="border-t border-gray-200 px-6 py-4">
                        <div class="ms-auto max-w-xs space-y-1 text-sm">
                            <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ currency(sale.subtotal) }}</span></div>
                            <div class="flex justify-between text-gray-600"><span>Impuesto</span><span>{{ currency(sale.tax) }}</span></div>
                            <div class="flex justify-between text-lg font-bold text-gray-900"><span>Total</span><span>{{ currency(sale.total) }}</span></div>
                        </div>
                        <p class="mt-4 text-xs text-gray-400">
                            Cada ítem generó automáticamente un movimiento de tipo <span class="font-semibold">venta</span> en el kardex referenciado por
                            {{ sale.sale_number }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
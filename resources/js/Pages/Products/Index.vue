<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { currency } from '@/composables/useCurrency';

defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const types = ['Tinto', 'Blanco', 'Rosado', 'Espumoso'];

const form = useForm({
    search: '',
    type: '',
});

function applyFilters() {
    router.get('/products', { search: form.search || undefined, type: form.type || undefined }, { preserveState: true, replace: true });
}

function destroy(product) {
    if (!confirm(`¿Eliminar el producto "${product.name}"? Sólo se permite si no tiene movimientos registrados en el kardex.`)) {
        return;
    }
    router.delete(route('products.destroy', product.id));
}
</script>
<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Inventario de Vinos</h2>
                <Link
                    :href="route('products.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500"
                >
                    + Nuevo producto
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- Filtros -->
                    <div class="flex flex-wrap items-end gap-4 border-b border-gray-200 p-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Buscar</label>
                            <input
                                v-model="form.search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Nombre, marca o tipo..."
                                class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select
                                v-model="form.type"
                                class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todos</option>
                                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <button
                            @click="applyFilters"
                            class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            Filtrar
                        </button>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Marca</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Presentación</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Precio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="product in products.data" :key="product.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ product.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ product.brand }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ product.type }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ product.presentation }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900">{{ currency(product.sale_price) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                                                        <span
                                        :class="product.current_stock <= product.min_stock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    >
                                        {{ product.current_stock }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('products.edit', product.id)" class="text-indigo-600 hover:text-indigo-900">Editar</Link>
                                    <DangerButton class="ms-4" @click="destroy(product)">Eliminar</DangerButton>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No se encontraron productos.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4" v-if="products.last_page > 1">
                        <Link
                            :href="products.prev_page_url || '#'"
                            class="text-sm text-gray-600 hover:text-gray-900"
                            :class="{ 'pointer-events-none opacity-40': !products.prev_page_url }"
                        >
                            ← Anterior
                        </Link>
                        <span class="text-sm text-gray-500">Página {{ products.current_page }} de {{ products.last_page }}</span>
                        <Link
                            :href="products.next_page_url || '#'"
                            class="text-sm text-gray-600 hover:text-gray-900"
                            :class="{ 'pointer-events-none opacity-40': !products.next_page_url }"
                        >
                            Siguiente →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
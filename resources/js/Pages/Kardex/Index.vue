<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    movements: { type: Object, required: true },
    products: { type: Array, required: true },
    movementTypes: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    product_id: '',
    type: '',
    reference: '',
});

function applyFilters() {
    router.get('/kardex', {
        product_id: form.product_id || undefined,
        type: form.type || undefined,
        reference: form.reference || undefined,
    }, { preserveState: true, replace: true });
}

// Formulario de registro de movimientos (entrada / ajuste) — el stock físico
// sólo se abastece a través del kardex.
const movementForm = useForm({
    product_id: '',
    movement_type: 'entrada',
    quantity: '',
    reference: '',
});

const movementTypesRegister = ['entrada', 'ajuste'];

function registerMovement() {
    movementForm.post(route('kardex.store'), {
        preserveScroll: true,
        onSuccess: () => movementForm.reset(),
    });
}

const typeBadge = {
    entrada: 'bg-green-100 text-green-800',
    venta: 'bg-red-100 text-red-800',
    ajuste: 'bg-amber-100 text-amber-800',
};

const dateFmt = (value) => new Date(value).toLocaleString('es-PE');
</script>

<template>
    <Head title="Kardex" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Kardex de movimientos</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap items-end gap-4 border-b border-gray-200 p-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Producto</label>
                            <select v-model="form.product_id" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de movimiento</label>
                            <select v-model="form.type" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="t in movementTypes" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Referencia</label>
                            <input v-model="form.reference" @keyup.enter="applyFilters" type="text" placeholder="V-20260001..." class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button @click="applyFilters" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">Filtrar</button>
                    </div>

                                        <!-- Registro de movimientos de entrada / ajuste -->
                    <div class="border-b border-gray-200 p-6">
                        <h3 class="mb-4 text-sm font-semibold text-gray-700">Registrar movimiento de entrada / ajuste</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Producto</label>
                                <select v-model="movementForm.product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled>Seleccione un producto…</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                                <InputError :message="movementForm.errors.product_id" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                <select v-model="movementForm.movement_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="t in movementTypesRegister" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <InputError :message="movementForm.errors.movement_type" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                                <TextInput v-model.number="movementForm.quantity" type="number" step="1" min="1" class="mt-1 block w-full" />
                                <InputError :message="movementForm.errors.quantity" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Referencia</label>
                                <TextInput v-model="movementForm.reference" type="text" placeholder="Factura, Guía…" class="mt-1 block w-full" />
                                <InputError :message="movementForm.errors.reference" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <PrimaryButton :disabled="movementForm.processing" @click="registerMovement">
                                Registrar movimiento
                            </PrimaryButton>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            El movimiento actualiza el stock físico del producto y se registra en el kardex con el stock anterior y nuevo.
                        </p>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tipo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cantidad</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock anterior</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock nuevo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Referencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="movement in movements.data" :key="movement.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ dateFmt(movement.created_at) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ movement.product?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span :class="typeBadge[movement.movement_type]" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize">{{ movement.movement_type }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium" :class="movement.quantity > 0 ? 'text-green-600' : 'text-red-600'">{{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">{{ movement.previous_stock }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900">{{ movement.new_stock }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ movement.reference || '—' }}</td>
                            </tr>
                            <tr v-if="movements.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Sin movimientos registrados.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4" v-if="movements.last_page > 1">
                        <Link :href="movements.prev_page_url || '#'" class="text-sm text-gray-600 hover:text-gray-900" :class="{ 'pointer-events-none opacity-40': !movements.prev_page_url }">← Anterior</Link>
                        <span class="text-sm text-gray-500">Página {{ movements.current_page }} de {{ movements.last_page }}</span>
                        <Link :href="movements.next_page_url || '#'" class="text-sm text-gray-600 hover:text-gray-900" :class="{ 'pointer-events-none opacity-40': !movements.next_page_url }">Siguiente →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

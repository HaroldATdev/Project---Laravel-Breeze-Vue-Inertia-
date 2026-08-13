<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { currency } from '@/composables/useCurrency';
import { ref } from 'vue';

const props = defineProps({
    products: { type: Array, required: true },
});

const selectedProductId = ref('');
const quantity = ref(1);
const lineItems = ref([]);

const form = useForm({
    customer_name: '',
    tax_rate: 19,
    items: [],
});

function addProduct() {
    if (!selectedProductId.value) {
        return;
    }
    const product = props.products.find((p) => p.id === Number(selectedProductId.value));
    if (!product) return;

    const existing = lineItems.value.find((item) => item.product_id === product.id);
    if (existing) {
        existing.quantity = Math.min(existing.quantity + Number(quantity.value), product.current_stock);
    } else {
        lineItems.value.push({
            product_id: product.id,
            product_name: product.name,
            unit_price: Number(product.sale_price),
            quantity: Number(quantity.value),
            max: product.current_stock,
        });
    }
    resetPicker();
}

function removeItem(index) {
    lineItems.value.splice(index, 1);
}

function resetPicker() {
    selectedProductId.value = '';
    quantity.value = 1;
}

const subtotal = () => lineItems.value.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);
const tax = () => (subtotal() * (Number(form.tax_rate) || 0)) / 100;
const total = () => subtotal() + tax();

function submit() {
    form.items = lineItems.value.map(({ product_id, quantity: qty }) => ({ product_id, quantity: qty }));
    form.post(route('sales.store'), {
        onSuccess: () => {
            lineItems.value = [];
            resetPicker();
        },
    });
}
</script>

<template>
    <Head title="Nueva Venta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Registrar venta</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 class="mb-4 text-sm font-semibold text-gray-700">Agregar producto a la venta</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <div class="sm:col-span-2">
                                <InputLabel value="Producto" />
                                <select v-model="selectedProductId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled>Seleccione un vino...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Cantidad" />
                                <TextInput v-model.number="quantity" type="number" min="1" class="mt-1 block w-full" />
                            </div>
                            <div class="flex items-end">
                                <PrimaryButton type="button" @click="addProduct">Agregar</PrimaryButton>
                            </div>
                        </div>
                        <InputError :message="form.errors.items" />
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg" v-if="lineItems.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Producto</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Precio</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cantidad</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Subtotal</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="(item, index) in lineItems" :key="item.product_id">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ item.product_name }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ currency(item.unit_price) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <input v-model.number="item.quantity" type="number" min="1" :max="item.max" class="w-20 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ currency(item.unit_price * item.quantity) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button" @click="removeItem(index)" class="text-sm text-red-600 hover:text-red-800">Quitar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel value="Cliente" />
                                <TextInput v-model="form.customer_name" type="text" class="mt-1 block w-full" placeholder="Cliente general" />
                            </div>
                            <div>
                                <InputLabel value="IVA / Impuesto (%)" />
                                <TextInput v-model.number="form.tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" />
                                <InputError :message="form.errors.tax_rate" />
                            </div>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ currency(subtotal()) }}</span></div>
                                <div class="flex justify-between text-gray-600"><span>Impuesto</span><span>{{ currency(tax()) }}</span></div>
                                <div class="flex justify-between text-lg font-bold text-gray-900"><span>Total</span><span>{{ currency(total()) }}</span></div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing || lineItems.length === 0">
                                {{ form.processing ? 'Procesando...' : 'Registrar venta (descontar stock)' }}
                            </PrimaryButton>
                            <Link :href="route('sales.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

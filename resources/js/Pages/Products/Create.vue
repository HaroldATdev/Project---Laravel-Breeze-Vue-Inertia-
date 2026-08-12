<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const types = ['Tinto', 'Blanco', 'Rosado', 'Espumoso'];
const presentations = ['500 ml', '750 ml', '1 L', '1.5 L', '3 L'];

const form = useForm({
    name: '',
    brand: '',
    type: 'Tinto',
    presentation: '750 ml',
    sale_price: '',
    initial_stock: '',
});

function submit() {
    form.post(route('products.store'));
}
</script>

<template>
    <Head title="Nuevo Producto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo producto</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Marca" />
                            <TextInput v-model="form.brand" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.brand" />
                        </div>
                        <div>
                            <InputLabel value="Tipo" />
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Presentación" />
                            <select v-model="form.presentation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="p in presentations" :key="p" :value="p">{{ p }}</option>
                            </select>
                            <InputError :message="form.errors.presentation" />
                        </div>
                        <div>
                            <InputLabel value="Precio de venta (CLP)" />
                            <TextInput v-model="form.sale_price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.sale_price" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Stock inicial" />
                        <TextInput v-model="form.initial_stock" type="number" min="0" class="mt-1 block w-full" required />
                        <p class="mt-1 text-xs text-gray-500">
                            El stock inicial se registra automáticamente como una entrada en el kardex.
                        </p>
                        <InputError :message="form.errors.initial_stock" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">Guardar producto</PrimaryButton>
                        <Link :href="route('products.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
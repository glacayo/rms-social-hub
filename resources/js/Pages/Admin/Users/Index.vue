<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
});

const form = useForm({});

const deleteUser = (id) => {
    if (confirm('¿Estás seguro de que querés borrar a este usuario?')) {
        form.delete(route('admin.users.destroy', id));
    }
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'super-admin': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'admin': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'editor': return 'bg-green-100 text-green-800 border-green-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Usuarios</h2>
                <Link
                    :href="route('admin.users.create')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-150"
                >
                    + Nuevo Usuario
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-md shadow-sm">
                    {{ $page.props.flash.success }}
                </div>
                
                <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-md shadow-sm">
                    {{ $page.props.flash.error }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado el</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ user.name }}
                                        <span v-if="$page.props.auth.user.id === user.id" class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded">Vos</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 py-1 text-xs border rounded-full font-semibold', getRoleBadgeClass(user.role)]">
                                            {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(user.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('admin.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</Link>
                                        <button 
                                            v-if="$page.props.auth.user.id !== user.id"
                                            @click="deleteUser(user.id)" 
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

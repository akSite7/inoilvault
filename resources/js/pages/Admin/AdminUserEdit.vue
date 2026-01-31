<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    username: props.user.username ?? '',
    email: props.user.email ?? '',
    password: '',
    role: props.user.role ?? 'user',
});

const submit = () => {
    form.put(`/admin/users/${props.user.id}`);
};
</script>

<template>
    <div class="w-full">
        <div class="mx-auto max-w-[1440px] p-5">
            <div class="rounded-xs border border-secondary/60 bg-accent p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-semibold">Редактирование пользователя</h1>
                    <Link
                        class="rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                        href="/admin?section=users"
                    >
                        Назад
                    </Link>
                </div>

                <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <div class="md:col-span-2">
                        <label class="text-sm text-text-secondary">Никнейм</label>
                        <input
                            v-model="form.username"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                            type="text"
                            maxlength="24"
                        />
                        <p v-if="form.errors.username" class="mt-2 text-xs text-rose-400">{{ form.errors.username }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm text-text-secondary">Почта</label>
                        <input
                            v-model="form.email"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                            type="email"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-xs text-rose-400">{{ form.errors.email }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm text-text-secondary">Новый пароль</label>
                        <input
                            v-model="form.password"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                            type="password"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-xs text-rose-400">{{ form.errors.password }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm text-text-secondary">Роль</label>
                        <select
                            v-model="form.role"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                        >
                            <option value="user">Пользователь</option>
                            <option value="moderator">Модератор</option>
                            <option value="admin">Админ</option>
                        </select>
                        <p v-if="form.errors.role" class="mt-2 text-xs text-rose-400">{{ form.errors.role }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <button
                            class="w-full rounded-xs bg-primary py-2 text-sm text-white transition hover:bg-primary-hover"
                            type="submit"
                            :disabled="form.processing"
                        >
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const submit = () => {
    form.post('/register');
};
</script>

<template>
    <div class="text-text-primary min-h-screen overflow-hidden">
        <div class="mx-auto flex min-h-screen w-full max-w-[1440px] flex-col items-center justify-center px-5 overflow-hidden">
            <div class="w-full max-w-[420px] rounded bg-accent p-8 pb-7 border border-secondary/60">
                <h1 class="text-center text-3xl font-semibold">Регистрация</h1>
                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="text-sm text-text-secondary" for="username">Никнейм</label>
                        <input
                            id="username"
                            v-model="form.username"
                            class="mt-2 w-full rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary focus:outline-none"
                            type="text"
                            autocomplete="username"
                        />
                        <p v-if="form.errors.username" class="mt-2 text-xs text-rose-400">{{ form.errors.username }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-text-secondary" for="email">Почта</label>
                        <input
                            id="email"
                            v-model="form.email"
                            class="mt-2 w-full rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary focus:outline-none"
                            type="email"
                            autocomplete="email"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-xs text-rose-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-text-secondary" for="password">Пароль</label>
                        <div class="relative mt-2">
                            <input
                                id="password"
                                v-model="form.password"
                                class="w-full rounded-xs bg-secondary px-3 py-2 pr-10 text-sm text-text-primary focus:outline-none"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                            />
                            <button
                                class="absolute right-3 top-1/2 -translate-y-1/2 border-l border-[#2d2d2d] pl-3 text-text-secondary transition hover:text-text-primary"
                                type="button"
                                @click="showPassword = !showPassword"
                            >
                                <svg class="cursor-pointer opacity-50" v-if="showPassword" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g transform="translate(-991.000000, -105.000000)" fill="#bfbfbf">
                                            <path d="M1003,112 C1005.76,112 1008,114.24 1008,117 C1008,117.65 1007.87,118.26 1007.64,118.83 L1010.56,121.75 C1012.07,120.49 1013.26,118.86 1013.99,117 C1012.26,112.61 1007.99,109.5 1002.99,109.5 C1001.59,109.5 1000.25,109.75 999.01,110.2 L1001.17,112.36 C1001.74,112.13 1002.35,112 1003,112 L1003,112 Z M993,109.27 L995.28,111.55 L995.74,112.01 C994.08,113.3 992.78,115.02 992,117 C993.73,121.39 998,124.5 1003,124.5 C1004.55,124.5 1006.03,124.2 1007.38,123.66 L1007.8,124.08 L1010.73,127 L1012,125.73 L994.27,108 L993,109.27 L993,109.27 Z M998.53,114.8 L1000.08,116.35 C1000.03,116.56 1000,116.78 1000,117 C1000,118.66 1001.34,120 1003,120 C1003.22,120 1003.44,119.97 1003.65,119.92 L1005.2,121.47 C1004.53,121.8 1003.79,122 1003,122 C1000.24,122 998,119.76 998,117 C998,116.21 998.2,115.47 998.53,114.8 L998.53,114.8 Z M1002.84,114.02 L1005.99,117.17 L1006.01,117.01 C1006.01,115.35 1004.67,114.01 1003.01,114.01 L1002.84,114.02 L1002.84,114.02 Z" />
                                        </g>
                                    </g>
                                </svg>
                                <svg class="cursor-pointer opacity-50" v-else aria-hidden="true" width="20" height="20" viewBox="0 0 28 28" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <path d="M14,5.25 C8.16666667,5.25 3.185,8.87833333 1.16666667,14 C3.185,19.1216667 8.16666667,22.75 14,22.75 C19.8333333,22.75 24.815,19.1216667 26.8333333,14 C24.815,8.87833333 19.8333333,5.25 14,5.25 Z M14,19.8333333 C10.78,19.8333333 8.16666667,17.22 8.16666667,14 C8.16666667,10.78 10.78,8.16666667 14,8.16666667 C17.22,8.16666667 19.8333333,10.78 19.8333333,14 C19.8333333,17.22 17.22,19.8333333 14,19.8333333 Z M14,10.5 C12.0633333,10.5 10.5,12.0633333 10.5,14 C10.5,15.9366667 12.0633333,17.5 14,17.5 C15.9366667,17.5 17.5,15.9366667 17.5,14 C17.5,12.0633333 15.9366667,10.5 14,10.5 Z" fill="#bfbfbf" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-xs text-rose-400">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-text-secondary" for="password_confirmation">Подтвердите пароль</label>
                        <div class="relative mt-2">
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                class="w-full rounded-xs bg-secondary px-3 py-2 pr-10 text-sm text-text-primary focus:outline-none"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                autocomplete="new-password"
                            />
                            <button
                                class="absolute right-3 top-1/2 -translate-y-1/2 border-l border-[#2d2d2d] pl-3 text-text-secondary transition hover:text-text-primary"
                                type="button"
                                @click="showPasswordConfirm = !showPasswordConfirm"
                            >
                                <svg class="cursor-pointer opacity-50" v-if="showPasswordConfirm" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g transform="translate(-991.000000, -105.000000)" fill="#bfbfbf">
                                            <path d="M1003,112 C1005.76,112 1008,114.24 1008,117 C1008,117.65 1007.87,118.26 1007.64,118.83 L1010.56,121.75 C1012.07,120.49 1013.26,118.86 1013.99,117 C1012.26,112.61 1007.99,109.5 1002.99,109.5 C1001.59,109.5 1000.25,109.75 999.01,110.2 L1001.17,112.36 C1001.74,112.13 1002.35,112 1003,112 L1003,112 Z M993,109.27 L995.28,111.55 L995.74,112.01 C994.08,113.3 992.78,115.02 992,117 C993.73,121.39 998,124.5 1003,124.5 C1004.55,124.5 1006.03,124.2 1007.38,123.66 L1007.8,124.08 L1010.73,127 L1012,125.73 L994.27,108 L993,109.27 L993,109.27 Z M998.53,114.8 L1000.08,116.35 C1000.03,116.56 1000,116.78 1000,117 C1000,118.66 1001.34,120 1003,120 C1003.22,120 1003.44,119.97 1003.65,119.92 L1005.2,121.47 C1004.53,121.8 1003.79,122 1003,122 C1000.24,122 998,119.76 998,117 C998,116.21 998.2,115.47 998.53,114.8 L998.53,114.8 Z M1002.84,114.02 L1005.99,117.17 L1006.01,117.01 C1006.01,115.35 1004.67,114.01 1003.01,114.01 L1002.84,114.02 L1002.84,114.02 Z" />
                                        </g>
                                    </g>
                                </svg>
                                <svg class="cursor-pointer opacity-50" v-else aria-hidden="true" width="20" height="20" viewBox="0 0 28 28" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <path d="M14,5.25 C8.16666667,5.25 3.185,8.87833333 1.16666667,14 C3.185,19.1216667 8.16666667,22.75 14,22.75 C19.8333333,22.75 24.815,19.1216667 26.8333333,14 C24.815,8.87833333 19.8333333,5.25 14,5.25 Z M14,19.8333333 C10.78,19.8333333 8.16666667,17.22 8.16666667,14 C8.16666667,10.78 10.78,8.16666667 14,8.16666667 C17.22,8.16666667 19.8333333,10.78 19.8333333,14 C19.8333333,17.22 17.22,19.8333333 14,19.8333333 Z M14,10.5 C12.0633333,10.5 10.5,12.0633333 10.5,14 C10.5,15.9366667 12.0633333,17.5 14,17.5 C15.9366667,17.5 17.5,15.9366667 17.5,14 C17.5,12.0633333 15.9366667,10.5 14,10.5 Z" fill="#bfbfbf" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-2 text-xs text-rose-400">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button
                        class="mt-2 w-full cursor-pointer rounded-xs bg-primary py-2 text-sm text-white transition hover:bg-primary-hover"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Зарегистрироваться
                    </button>
                </form>

                <p class="mt-3 text-center text-xs text-text-secondary">
                    Уже зарегистрированы?
                    <a class="text-text-primary hover:text-white" href="/login">Войти</a>
                </p>
            </div>
        </div>
    </div>
</template>

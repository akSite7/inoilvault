<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const successMessage = computed(() => page.props?.flash?.success);

const form = useForm({
    code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post('/verification');
};

const resend = () => {
    resendForm.post('/verification/resend');
};

</script>

<template>
    <div class="text-text-primary min-h-screen overflow-hidden">
        <div class="mx-auto flex min-h-screen w-full max-w-[1440px] flex-col items-center justify-center gap-10 px-5 overflow-hidden">
            <div class="w-full max-w-[420px] rounded bg-accent p-8 pb-7 border border-secondary/60">
                <h1 class="text-center text-3xl font-semibold">Подтвердите почту</h1>

            

                <form class="mt-2 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="text-sm text-text-secondary" for="code">Код</label>
                        <div class="relative mt-2">
                            <input
                                id="code"
                                v-model="form.code"
                                class="w-full rounded-xs bg-secondary px-3 py-2 pr-10 text-sm text-text-primary focus:outline-none"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                            />
                            <button
                                class="absolute right-3 top-1/2 -translate-y-1/2 border-l border-[#2d2d2d] pl-3 text-text-secondary transition hover:text-text-primary"
                                type="button"
                                :disabled="resendForm.processing"
                                aria-label="Отправить код снова"
                                @click="resend"
                            >
                               <svg fill="#737373" class="cursor-pointer" width="16px" height="16px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4,12a1,1,0,0,1-2,0A9.983,9.983,0,0,1,18.242,4.206V2.758a1,1,0,1,1,2,0v4a1,1,0,0,1-1,1h-4a1,1,0,0,1,0-2h1.743A7.986,7.986,0,0,0,4,12Zm17-1a1,1,0,0,0-1,1A7.986,7.986,0,0,1,7.015,18.242H8.757a1,1,0,1,0,0-2h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,2,0V19.794A9.984,9.984,0,0,0,22,12,1,1,0,0,0,21,11Z"/></svg>

                            </button>
                        </div>
       
                        <p v-if="form.errors.code" class="mt-2 text-xs text-rose-400">{{ form.errors.code }}</p>
                    </div>

                    <button
                        class="mt-2 w-full cursor-pointer rounded-xs bg-primary py-2 text-sm text-white transition hover:bg-primary-hover"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Подтвердить
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-text-secondary">
                    Уже зарегистрированы?
                    <a class="text-text-primary hover:text-white" href="/login">Войти</a>
                </p>
            </div>
        </div>
    </div>
</template>


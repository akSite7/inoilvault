<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    is_edit: {
        type: Boolean,
        default: false,
    },
    source: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: props.source?.name || '',
});

const title = computed(() => (props.is_edit ? 'Редактирование первоисточника' : 'Создание первоисточника'));

const submit = () => {
    if (props.is_edit && props.source) {
        form.put(`/admin/sources/${props.source.id}`);
        return;
    }
    form.post('/admin/sources');
};
</script>

<template>
    <div class="w-full">
        <div class="mx-auto max-w-[1440px] p-5">
            <div class="rounded-xs border border-secondary/60 bg-accent p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-semibold">{{ title }}</h1>
                    <Link
                        class="rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                        href="/admin?section=sources"
                    >
                        Назад
                    </Link>
                </div>

                <form class="mt-6 grid gap-4" @submit.prevent="submit">
                    <div>
                        <label class="text-sm text-text-secondary">Название</label>
                        <input
                            v-model="form.name"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                            type="text"
                        />
                        <p v-if="form.errors.name" class="mt-2 text-xs text-rose-400">{{ form.errors.name }}</p>
                    </div>

                    <button
                        class="w-full rounded-xs bg-primary py-2 text-sm text-white transition hover:bg-primary-hover"
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ props.is_edit ? 'Сохранить' : 'Создать' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

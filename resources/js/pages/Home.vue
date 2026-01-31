<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    recentAnime: {
        type: Array,
        default: () => [],
    },
});

const sliderRef = ref(null);

const hasRecent = computed(() => props.recentAnime.length > 0);
const recentCount = computed(() => props.recentAnime.length);

const quickActions = [
    {
        title: 'Каталог',
        description: 'Смотри все тайтлы, фильтруй по жанрам и году релиза.',
        href: '/anime',
        cta: 'Открыть каталог',
    },
    {
        title: 'Мой профиль',
        description: 'Собирай личный список просмотренного и планов.',
        href: '/profile',
        cta: 'Перейти в профиль',
    },
    {
        title: 'Друзья',
        description: 'Находи друзей и сравнивай вкусы в аниме.',
        href: '/friends',
        cta: 'Смотреть друзей',
    },
];

const getPrimaryAltTitle = (value) => {
    if (!value) return '—';
    const first = value
        .split(/\s*[|,;]\s*/)
        .map((title) => title.trim())
        .filter(Boolean)[0];
    return first || '—';
};

const scrollSlider = (direction) => {
    const container = sliderRef.value;
    if (!container) return;
    const step = Math.max(container.clientWidth * 0.85, 240);
    container.scrollBy({ left: step * direction, behavior: 'smooth' });
};
</script>

<template>
    <div class="w-full">
        <section class="mx-auto max-w-[1440px] px-5 py-8">
            <div class="relative mt-5">
                <div class="rounded border border-secondary/60 bg-accent p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-5">
                        <h2 class="text-2xl font-semibold text-text-primary">Последние добавленные аниме</h2>
                        <Link class="text-xs font-semibold uppercase tracking-[0.18em] text-primary" href="/anime">
                            Смотреть все
                        </Link>
                    </div>

                    <div
                        v-if="hasRecent"
                        ref="sliderRef"
                        class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <Link
                            v-for="item in recentAnime"
                            :key="item.id"
                            :href="`/anime/${item.id}`"
                            class="group flex w-[140px] shrink-0 snap-start flex-col"
                        >
                            <div class="aspect-[2/3] w-full overflow-hidden rounded-xs bg-secondary">
                                <img
                                    v-if="item.cover_url"
                                    :src="item.cover_url"
                                    :alt="item.title"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-xs text-text-secondary">Нет обложки</div>
                            </div>
                            <div class="mt-2 text-xs italic text-text-secondary line-clamp-2">
                                {{ getPrimaryAltTitle(item.alt_title) }}
                            </div>
                            <div class="mt-1 text-sm font-semibold text-text-primary line-clamp-2">
                                {{ item.title }}
                            </div>
                        </Link>
                    </div>

                    <div v-else class="rounded-xs bg-secondary p-6 text-sm text-text-secondary">
                        Новые аниме пока не добавлены.
                    </div>
                </div>

                <button
                    v-if="hasRecent"
                    class="absolute -left-[50px] top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-xs bg-secondary/90 text-text-primary backdrop-blur transition hover:bg-secondary lg:flex"
                    type="button"
                    aria-label="Назад"
                    @click="scrollSlider(-1)"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button
                    v-if="hasRecent"
                    class="absolute -right-[50px] top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-xs bg-secondary/90 text-text-primary backdrop-blur transition hover:bg-secondary lg:flex"
                    type="button"
                    aria-label="Вперед"
                    @click="scrollSlider(1)"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const success = page.props?.flash?.success;

const props = defineProps({
    genres: {
        type: Array,
        default: () => [],
    },
    studios: {
        type: Array,
        default: () => [],
    },
    characters: {
        type: Array,
        default: () => [],
    },
    sources: {
        type: Array,
        default: () => [],
    },
    animeOptions: {
        type: Array,
        default: () => [],
    },
    is_edit: {
        type: Boolean,
        default: false,
    },
    anime: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    title: props.anime?.title || '',
    alt_title: props.anime?.alt_title || '',
    type: props.anime?.type || '',
    genres: props.anime?.genres || [],
    episodes: props.anime?.episodes ?? '',
    status: props.anime?.status || '',
    source: props.anime?.source || '',
    season_date: props.anime?.season_date || '',
    year: props.anime?.year || '',
    studio_id: props.anime?.studio_id || '',
    studios: props.anime?.studios || [],
    mpaa_rating: props.anime?.mpaa_rating || '',
    age_rating: props.anime?.age_rating || '',
    duration: props.anime?.duration || '',
    main_characters: props.anime?.main_characters || [],
    related_items: props.anime?.related_items || [],
    description: props.anime?.description || '',
    trailer_url: props.anime?.trailer_url || '',
    cover: null,
    keep_frames: props.anime?.frame_paths ? [...props.anime.frame_paths] : [],
    frames: [],
});

const altTitles = ref(
    props.anime?.alt_title
        ? props.anime.alt_title
              .split(/\s*[|,;]\s*/)
              .map((title) => title.trim())
              .filter(Boolean)
        : [''],
);

const relatedItems = ref(
    props.anime?.related_items?.length
        ? props.anime.related_items.map((item) => ({
              anime_id: item.anime_id ?? '',
              type: item.type ?? '',
          }))
        : props.anime?.related_anime_id
          ? [
                {
                    anime_id: props.anime.related_anime_id,
                    type: props.anime.related_type || '',
                },
            ]
          : [
                {
                    anime_id: '',
                    type: '',
                },
            ],
);
const relatedSearch = ref(
    relatedItems.value.map((item) => {
        const found = props.animeOptions.find((option) => option.id === item.anime_id);
        return found ? found.title : '';
    }),
);
const relatedDropdownOpen = ref(relatedItems.value.map(() => false));

const existingFrames = ref(
    props.anime?.frame_paths?.map((path, index) => ({
        path,
        url: props.anime?.frames?.[index] || '',
    })) || [],
);

const localStudios = ref([...props.studios]);
const localCharacters = ref([...props.characters]);
const studioRows = ref(
    props.anime?.studios?.length
        ? props.anime.studios.map((id) => ({ id: Number(id) }))
        : props.anime?.studio_id
          ? [{ id: Number(props.anime.studio_id) }]
          : [{ id: '' }],
);
const heroRows = ref(
    props.anime?.main_characters?.length
        ? props.anime.main_characters.map((item) => ({
              id: item?.id ? Number(item.id) : '',
          }))
        : props.anime?.main_character_id
          ? [{ id: Number(props.anime.main_character_id) }]
          : [{ id: '' }],
);
const heroSearch = ref(
    heroRows.value.map((row) => {
        const found = props.characters.find((character) => character.id === row.id);
        return found ? found.name : '';
    }),
);
const heroDropdownOpen = ref(heroRows.value.map(() => false));
const showCharacterModal = ref(false);
const newCharacter = ref({ name: '', voice_actor: '' });
const newCharacterErrors = ref({});
const newCharacterSaving = ref(false);
const showStudioModal = ref(false);
const newStudio = ref({ name: '' });
const newStudioErrors = ref({});
const newStudioSaving = ref(false);

const getVoiceActor = (id) =>
    localCharacters.value.find((character) => character.id === id)?.voice_actor || '';

const getAvailableStudios = (index) => {
    const selectedIds = studioRows.value
        .map((row, rowIndex) => (rowIndex === index ? null : Number(row.id)))
        .filter((id) => Number.isFinite(id) && id > 0);
    return localStudios.value.filter((studio) => !selectedIds.includes(studio.id));
};

const getAvailableCharacters = (index) => {
    const selectedIds = heroRows.value
        .map((row, rowIndex) => (rowIndex === index ? null : Number(row.id)))
        .filter((id) => Number.isFinite(id) && id > 0);
    return localCharacters.value.filter((character) => !selectedIds.includes(character.id));
};

const coverPreview = computed(() => {
    if (!form.cover) return props.anime?.cover_url || '';
    return URL.createObjectURL(form.cover);
});

const framePreviews = computed(() => [
    ...existingFrames.value.map((frame) => ({
        key: `existing-${frame.path}`,
        url: frame.url,
        existing: true,
        path: frame.path,
    })),
    ...form.frames.map((file, index) => ({
        key: `new-${index}-${file.name}`,
        url: URL.createObjectURL(file),
        existing: false,
        index,
    })),
]);

const onCoverChange = (event) => {
    form.cover = event.target.files?.[0] ?? null;
};

const onFramesChange = (event) => {
    const files = Array.from(event.target.files ?? []);
    const merged = [...form.frames, ...files];
    const totalCount = existingFrames.value.length + merged.length;
    if (totalCount > 20) {
        form.setError('frames', 'Можно загрузить до 20 кадров.');
    } else {
        form.clearErrors('frames');
    }
    const availableSlots = Math.max(0, 20 - existingFrames.value.length);
    form.frames = merged.slice(0, availableSlots);
};

const removeFrame = (item) => {
    if (item.existing) {
        existingFrames.value = existingFrames.value.filter((frame) => frame.path !== item.path);
        form.keep_frames = existingFrames.value.map((frame) => frame.path);
        return;
    }
    form.frames = form.frames.filter((_, index) => index !== item.index);
};

const submit = () => {
    form.alt_title = altTitles.value.map((title) => title.trim()).filter(Boolean).join(' | ');
    form.studios = studioRows.value
        .map((row) => Number(row.id))
        .filter((id) => Number.isFinite(id) && id > 0)
        .slice(0, 5);
    form.studio_id = form.studios[0] ?? '';
    form.main_characters = heroRows.value
        .map((row) =>
            localCharacters.value.find((character) => character.id === Number(row.id)),
        )
        .filter(Boolean)
        .map((character) => ({
            id: character.id,
            name: character.name,
            voice_actor: character.voice_actor || '',
        }));
    form.related_items = relatedItems.value
        .map((item) => ({
            anime_id: item.anime_id || null,
            type: item.type || '',
        }))
        .filter((item) => item.anime_id && item.type);
    form.keep_frames = existingFrames.value.map((frame) => frame.path);
    if (props.is_edit && props.anime?.id) {
        form.put(`/admin/anime/${props.anime.id}`);
        return;
    }
    form.post('/admin/anime');
};

const addAltTitle = () => {
    if (altTitles.value.length >= 5) {
        return;
    }
    altTitles.value.push('');
};

const removeAltTitle = (index) => {
    if (altTitles.value.length === 1) {
        altTitles.value[0] = '';
        return;
    }
    altTitles.value.splice(index, 1);
};

const addRelatedItem = () => {
    relatedItems.value.push({
        anime_id: '',
        type: '',
    });
    relatedSearch.value.push('');
    relatedDropdownOpen.value.push(false);
};

const removeRelatedItem = (index) => {
    if (relatedItems.value.length === 1) {
        relatedItems.value[0] = {
            anime_id: '',
            type: '',
        };
        relatedSearch.value[0] = '';
        relatedDropdownOpen.value[0] = false;
        return;
    }
    relatedItems.value.splice(index, 1);
    relatedSearch.value.splice(index, 1);
    relatedDropdownOpen.value.splice(index, 1);
};

const getRelatedOptions = (index) => {
    const query = (relatedSearch.value[index] || '').trim().toLowerCase();
    const selectedId = Number(relatedItems.value[index]?.anime_id);
    return props.animeOptions.filter((item) => {
        if (selectedId && item.id === selectedId) return true;
        if (!query) return true;
        return item.title?.toLowerCase().includes(query);
    });
};

const openRelatedDropdown = (index) => {
    relatedDropdownOpen.value[index] = true;
};

const closeRelatedDropdown = (index) => {
    setTimeout(() => {
        relatedDropdownOpen.value[index] = false;
    }, 150);
};

const selectRelatedAnime = (index, anime) => {
    relatedItems.value[index].anime_id = anime.id;
    relatedSearch.value[index] = anime.title;
    relatedDropdownOpen.value[index] = false;
};

const onRelatedSearchInput = (index) => {
    const query = (relatedSearch.value[index] || '').trim().toLowerCase();
    const currentId = Number(relatedItems.value[index]?.anime_id);
    if (!query) {
        relatedItems.value[index].anime_id = '';
        return;
    }
    const currentTitle = props.animeOptions.find((item) => item.id === currentId)?.title?.toLowerCase();
    if (currentTitle && currentTitle !== query) {
        relatedItems.value[index].anime_id = '';
    }
};

const addStudioRow = () => {
    if (studioRows.value.length >= 5) {
        return;
    }
    studioRows.value.push({ id: '' });
};

const removeStudioRow = (index) => {
    if (studioRows.value.length === 1) {
        studioRows.value[0].id = '';
        return;
    }
    studioRows.value.splice(index, 1);
};

const addHeroRow = () => {
    heroRows.value.push({ id: '' });
    heroSearch.value.push('');
    heroDropdownOpen.value.push(false);
};

const removeHeroRow = (index) => {
    if (heroRows.value.length === 1) {
        heroRows.value[0].id = '';
        heroSearch.value[0] = '';
        heroDropdownOpen.value[0] = false;
        return;
    }
    heroRows.value.splice(index, 1);
    heroSearch.value.splice(index, 1);
    heroDropdownOpen.value.splice(index, 1);
};

const openCharacterModal = () => {
    newCharacter.value = { name: '', voice_actor: '' };
    newCharacterErrors.value = {};
    showCharacterModal.value = true;
};

const getHeroOptions = (index) => {
    const query = (heroSearch.value[index] || '').trim().toLowerCase();
    const selectedId = Number(heroRows.value[index]?.id);
    const selectedIds = heroRows.value
        .map((row, rowIndex) => (rowIndex === index ? null : Number(row.id)))
        .filter((id) => Number.isFinite(id) && id > 0);
    return props.characters.filter((character) => {
        if (selectedId && character.id === selectedId) return true;
        if (selectedIds.includes(character.id)) return false;
        if (!query) return true;
        return character.name?.toLowerCase().includes(query);
    });
};

const openHeroDropdown = (index) => {
    heroDropdownOpen.value[index] = true;
};

const closeHeroDropdown = (index) => {
    setTimeout(() => {
        heroDropdownOpen.value[index] = false;
    }, 150);
};

const selectHero = (index, character) => {
    heroRows.value[index].id = character.id;
    heroSearch.value[index] = character.name;
    heroDropdownOpen.value[index] = false;
};

const onHeroSearchInput = (index) => {
    const query = (heroSearch.value[index] || '').trim().toLowerCase();
    const currentId = Number(heroRows.value[index]?.id);
    if (!query) {
        heroRows.value[index].id = '';
        return;
    }
    const currentName = props.characters.find((item) => item.id === currentId)?.name?.toLowerCase();
    if (currentName && currentName !== query) {
        heroRows.value[index].id = '';
    }
};

const closeCharacterModal = () => {
    showCharacterModal.value = false;
};

const openStudioModal = () => {
    newStudio.value = { name: '' };
    newStudioErrors.value = {};
    showStudioModal.value = true;
};

const closeStudioModal = () => {
    showStudioModal.value = false;
};

const createCharacter = async () => {
    newCharacterErrors.value = {};
    newCharacterSaving.value = true;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch('/admin/characters', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                name: newCharacter.value.name,
                voice_actor: newCharacter.value.voice_actor,
            }),
        });

        if (response.status === 422) {
            const data = await response.json();
            newCharacterErrors.value = data.errors || {};
            return;
        }

        if (!response.ok) {
            newCharacterErrors.value = { name: ['Не удалось сохранить персонажа.'] };
            return;
        }

        const created = await response.json();
        localCharacters.value.push(created);
        const emptyRow = heroRows.value.find((row) => !row.id);
        if (emptyRow) {
            emptyRow.id = created.id;
        } else {
            heroRows.value.push({ id: created.id });
        }
        showCharacterModal.value = false;
    } catch (error) {
        newCharacterErrors.value = { name: ['Ошибка сети.'] };
    } finally {
        newCharacterSaving.value = false;
    }
};

const createStudio = async () => {
    newStudioErrors.value = {};
    newStudioSaving.value = true;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch('/admin/studios', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ name: newStudio.value.name }),
        });

        if (response.status === 422) {
            const data = await response.json();
            newStudioErrors.value = data.errors || {};
            return;
        }

        if (!response.ok) {
            newStudioErrors.value = { name: ['Не удалось сохранить студию.'] };
            return;
        }

        const created = await response.json();
        localStudios.value.push(created);
        const emptyRow = studioRows.value.find((row) => !row.id);
        if (emptyRow) {
            emptyRow.id = Number(created.id);
        } else if (studioRows.value.length < 5) {
            studioRows.value.push({ id: Number(created.id) });
        }
        closeStudioModal();
    } catch (error) {
        newStudioErrors.value = { name: ['Не удалось сохранить студию.'] };
    } finally {
        newStudioSaving.value = false;
    }
};
</script>

<template>
    <div class="w-full">
        <div class="mx-auto max-w-[1440px] p-5">
            <div class="space-y-6">
                <div class="rounded-xs border border-secondary/60 bg-accent p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-semibold">{{ props.is_edit ? 'Редактировать аниме' : 'Добавить аниме' }}</h1>
                        </div>
                        <Link
                            class="rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                            href="/admin?section=anime"
                        >
                            Назад
                        </Link>
                    </div>

                    <div v-if="success" class="mt-4 rounded-xs bg-secondary px-4 py-3 text-sm text-text-primary">
                        {{ success }}
                    </div>
                </div>

                <form class="grid gap-6" @submit.prevent="submit">
                    <section class="rounded-xs border border-secondary/60 bg-accent p-5">
                        <div class="text-sm font-semibold text-text-primary">Основные данные</div>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-xs text-text-secondary">Обложка</label>
                                <label class="mt-2 flex flex-col items-center justify-center rounded-xs border border-dashed border-secondary bg-secondary/30 px-4 py-8 text-xs text-text-secondary">
                                    <span>Перетащите файл или <span class="text-text-primary">выберите</span></span>
                                    <input
                                        class="hidden"
                                        type="file"
                                        accept="image/*"
                                        @change="onCoverChange"
                                    />
                                </label>
                                <div v-if="coverPreview" class="mt-3 flex items-center gap-3">
                                    <img :src="coverPreview" alt="" class="h-20 w-14 rounded-xs object-cover" />
                                    <span class="text-xs text-text-secondary">Предпросмотр обложки</span>
                                </div>
                                <p v-if="form.errors.cover" class="mt-2 text-xs text-rose-400">{{ form.errors.cover }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Название</label>
                                <input
                                    v-model="form.title"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                    type="text"
                                    placeholder="Название"
                                />
                                <p v-if="form.errors.title" class="mt-2 text-xs text-rose-400">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Альтернативные названия</label>
                                <div class="mt-2 space-y-2">
                                    <div v-for="(title, index) in altTitles" :key="index" class="flex gap-2">
                                        <input
                                            v-model="altTitles[index]"
                                            class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                            type="text"
                                            placeholder="Альтернативное название"
                                        />
                                        <button
                                            v-if="altTitles.length > 1"
                                            class="rounded-xs bg-secondary px-3 py-2 text-sm text-text-secondary transition hover:bg-secondary-hover"
                                            type="button"
                                            @click="removeAltTitle(index)"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                                <p v-if="form.errors.alt_title" class="mt-2 text-xs text-rose-400">{{ form.errors.alt_title }}</p>
                                <div class="mt-4 flex justify-center">
                                    <button
                                        class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover disabled:cursor-not-allowed disabled:opacity-50"
                                        type="button"
                                        @click="addAltTitle"
                                        :disabled="altTitles.length >= 5"
                                    >
                                        + Добавить альтернативное название
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xs border border-secondary/60 bg-accent p-5">
                        <div class="text-sm font-semibold text-text-primary">Дополнительные данные</div>

                        <div class="mt-4 space-y-4">
                                <div>
                                    <label class="text-xs text-text-secondary">Тип сериала</label>
                                    <select
                                        v-model="form.type"
                                        class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                    >
                                        <option value="" disabled hidden>Выберите тип</option>
                                        <option value="TV">ТВ сериал</option>
                                        <option value="Фильм">Фильм</option>
                                        <option value="OVA">OVA</option>
                                        <option value="ONA">ONA</option>
                                        <option value="Спешл">Спешл</option>
                                    </select>
                                    <p v-if="form.errors.type" class="mt-2 text-xs text-rose-400">{{ form.errors.type }}</p>
                                </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Количество эпизодов</label>
                                    <input
                                        v-model="form.episodes"
                                        class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                        type="number"
                                        min="0"
                                        placeholder="Количество эпизодов"
                                    />
                                    <p v-if="form.errors.episodes" class="mt-2 text-xs text-rose-400">{{ form.errors.episodes }}</p>
                                </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Жанр</label>
                                    <div class="mt-2 rounded-xs border border-secondary bg-secondary/30 px-4 py-3">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <label
                                                v-for="genre in genres"
                                                :key="genre.id"
                                                class="flex items-center gap-2 text-xs text-text-secondary"
                                            >
                                                <input v-model="form.genres" :value="genre.name" type="checkbox" class="filter-checkbox cursor-pointer" />
                                                {{ genre.name }}
                                            </label>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.genres" class="mt-2 text-xs text-rose-400">{{ form.errors.genres }}</p>
                                </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Статус</label>
                                    <select
                                        v-model="form.status"
                                        class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                    >
                                        <option value="" disabled hidden>Выберите статус</option>
                                        <option value="Вышел">Вышел</option>
                                        <option value="Онгоинг">Онгоинг</option>
                                        <option value="Анонс">Анонс</option>
                                    </select>
                                    <p v-if="form.errors.status" class="mt-2 text-xs text-rose-400">{{ form.errors.status }}</p>
                                </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Первоисточник</label>
                                    <select
                                        v-model="form.source"
                                        class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                    >
                                        <option value="" disabled hidden>Выберите первоисточник</option>
                                        <option v-for="source in sources" :key="source.id" :value="source.name">
                                            {{ source.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.source" class="mt-2 text-xs text-rose-400">{{ form.errors.source }}</p>
                                </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Сезон</label>
                                    <input
                                        v-model="form.season_date"
                                        class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                        type="date"
                                    />
                                    <p v-if="form.errors.season_date" class="mt-2 text-xs text-rose-400">{{ form.errors.season_date }}</p>
                                </div>

                            <div>
                                <label class="text-xs text-text-secondary">Выпуск</label>
                                <input
                                    v-model="form.year"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="Год выпуска"
                                />
                            </div>

                                <div>
                                    <label class="text-xs text-text-secondary">Студии</label>
                                    <div class="mt-2 space-y-2">
                                        <div
                                            v-for="(row, index) in studioRows"
                                            :key="`studio-row-${index}`"
                                            class="grid gap-2 md:grid-cols-[1fr_auto] items-start"
                                        >
                                            <select
                                                v-model.number="studioRows[index].id"
                                                class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                            >
                                                <option value="" disabled hidden>Выберите студию</option>
                                                <option v-for="studio in getAvailableStudios(index)" :key="studio.id" :value="studio.id">
                                                    {{ studio.name }}
                                                </option>
                                            </select>
                                            <button
                                                v-if="studioRows.length > 1"
                                                class="rounded-xs bg-secondary px-3 py-2 text-sm text-text-secondary transition hover:bg-secondary-hover"
                                                type="button"
                                                @click="removeStudioRow(index)"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.studio_id" class="mt-2 text-xs text-rose-400">{{ form.errors.studio_id }}</p>
                                    <p v-if="form.errors.studios" class="mt-2 text-xs text-rose-400">{{ form.errors.studios }}</p>
                                    <div class="mt-4 flex justify-center gap-3">
                                        <button
                                            class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover disabled:cursor-not-allowed disabled:opacity-50"
                                            type="button"
                                            @click="addStudioRow"
                                            :disabled="studioRows.length >= 5"
                                        >
                                            + Добавить поле студии
                                        </button>
                                        <button
                                            class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover"
                                            type="button"
                                            @click="openStudioModal"
                                        >
                                            + Добавить студию
                                        </button>
                                    </div>
                                </div>

                            <div>
                                <label class="text-xs text-text-secondary">MPAA Рейтинг</label>
                                <select
                                    v-model="form.mpaa_rating"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                >
                                    <option value="" disabled hidden>Выберите рейтинг</option>
                                    <option value="G">G</option>
                                    <option value="PG">PG</option>
                                    <option value="PG-13">PG-13</option>
                                    <option value="R-17">R-17</option>
                                    <option value="R+">R+</option>
                                </select>
                                <p v-if="form.errors.mpaa_rating" class="mt-2 text-xs text-rose-400">{{ form.errors.mpaa_rating }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Возрастное ограничение</label>
                                <select
                                    v-model="form.age_rating"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                >
                                    <option value="" disabled hidden>Выберите ограничение</option>
                                    <option value="6+">6+</option>
                                    <option value="12+">12+</option>
                                    <option value="16+">16+</option>
                                    <option value="18+">18+</option>
                                </select>
                                <p v-if="form.errors.age_rating" class="mt-2 text-xs text-rose-400">{{ form.errors.age_rating }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Длительность эпизода</label>
                                <input
                                    v-model="form.duration"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                    type="text"
                                    placeholder="23 мин."
                                />
                                <p v-if="form.errors.duration" class="mt-2 text-xs text-rose-400">{{ form.errors.duration }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Главные герои</label>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(row, index) in heroRows"
                                        :key="`hero-row-${index}`"
                                        class="grid gap-2 md:grid-cols-[1fr_1fr_auto] items-start"
                                    >
                                        <div class="space-y-2 relative">
                                            <input
                                                v-model="heroSearch[index]"
                                                class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                                type="text"
                                                placeholder="Поиск героя..."
                                                @focus="openHeroDropdown(index)"
                                                @blur="closeHeroDropdown(index)"
                                                @input="onHeroSearchInput(index)"
                                            />
                                            <div
                                                v-if="heroDropdownOpen[index]"
                                                class="absolute z-20 mt-1 w-full max-h-48 overflow-auto rounded-xs border border-secondary bg-secondary p-1 shadow-lg"
                                            >
                                                <button
                                                    v-for="character in getHeroOptions(index)"
                                                    :key="character.id"
                                                    type="button"
                                                    class="w-full rounded-xs px-3 py-2 text-left text-sm text-text-primary hover:bg-secondary-hover"
                                                    @mousedown.prevent="selectHero(index, character)"
                                                >
                                                    {{ character.name }}
                                                </button>
                                                <div
                                                    v-if="!getHeroOptions(index).length"
                                                    class="px-3 py-2 text-xs text-text-secondary"
                                                >
                                                    Ничего не найдено.
                                                </div>
                                            </div>
                                            <div v-if="heroRows[index].id" class="text-xs text-text-secondary">
                                                Выбрано: {{
                                                    props.characters.find((item) => item.id === heroRows[index].id)?.name ||
                                                        '—'
                                                }}
                                            </div>
                                        </div>
                                        <input
                                            :value="getVoiceActor(row.id)"
                                            class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary/70 focus:outline-none"
                                            type="text"
                                            placeholder="Актер озвучки"
                                            readonly
                                            disabled
                                        />
                                        <button
                                            v-if="heroRows.length > 1"
                                            class="rounded-xs bg-secondary px-3 py-2 text-sm text-text-secondary transition hover:bg-secondary-hover"
                                            type="button"
                                            @click="removeHeroRow(index)"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                                <p v-if="form.errors.main_characters" class="mt-2 text-xs text-rose-400">
                                    {{ form.errors.main_characters }}
                                </p>
                                <div class="mt-4 flex justify-center gap-3">
                                    <button
                                        class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover"
                                        type="button"
                                        @click="addHeroRow"
                                    >
                                        + Добавить поле героя
                                    </button>
                                    <button
                                        class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover"
                                        type="button"
                                        @click="openCharacterModal"
                                    >
                                        + Добавить героя
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs text-text-secondary">Описание</label>
                                <textarea
                                    v-model="form.description"
                                    class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                    rows="4"
                                    placeholder="Описание"
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-xs text-rose-400">{{ form.errors.description }}</p>
                            </div>

                        </div>
                    </section>



                    <section class="rounded-xs border border-secondary/60 bg-accent p-5">
                        <div class="text-sm font-semibold text-text-primary">Связанное</div>
                        <div class="mt-4 space-y-3">
                            <div
                                v-for="(item, index) in relatedItems"
                                :key="`related-${index}`"
                                class="grid gap-3 md:grid-cols-[1fr_1fr_auto] items-start"
                            >
                                <select
                                    v-model="relatedItems[index].type"
                                    class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none"
                                >
                                    <option value="" disabled hidden>Тип связи</option>
                                    <option value="Предыстория">Предыстория</option>
                                    <option value="Продолжение">Продолжение</option>
                                    <option value="Альтернативная история">Альтернативная история</option>
                                    <option value="Спешл">Спешл</option>
                                    <option value="ONA">ONA</option>
                                    <option value="OVA">OVA</option>
                                </select>
                                <div class="space-y-2 relative">
                                    <input
                                        v-model="relatedSearch[index]"
                                        class="w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                        type="text"
                                        placeholder="Поиск аниме..."
                                        @focus="openRelatedDropdown(index)"
                                        @blur="closeRelatedDropdown(index)"
                                        @input="onRelatedSearchInput(index)"
                                    />
                                    <div
                                        v-if="relatedDropdownOpen[index]"
                                        class="absolute z-20 mt-1 w-full max-h-48 overflow-auto rounded-xs border border-secondary bg-secondary p-1 shadow-lg"
                                    >
                                        <button
                                            v-for="anime in getRelatedOptions(index)"
                                            :key="anime.id"
                                            type="button"
                                            class="w-full rounded-xs px-3 py-2 text-left text-sm text-text-primary hover:bg-secondary-hover"
                                            @mousedown.prevent="selectRelatedAnime(index, anime)"
                                        >
                                            {{ anime.title }}
                                        </button>
                                        <div
                                            v-if="!getRelatedOptions(index).length"
                                            class="px-3 py-2 text-xs text-text-secondary"
                                        >
                                            Ничего не найдено.
                                        </div>
                                    </div>
                                    <div v-if="relatedItems[index].anime_id" class="text-xs text-text-secondary">
                                        Выбрано: {{
                                            props.animeOptions.find((item) => item.id === relatedItems[index].anime_id)?.title ||
                                                '—'
                                        }}
                                    </div>
                                </div>
                                <button
                                    v-if="relatedItems.length > 1"
                                    class="rounded-xs bg-secondary px-3 py-2 text-sm text-text-secondary transition hover:bg-secondary-hover"
                                    type="button"
                                    @click="removeRelatedItem(index)"
                                >
                                    Х
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.related_items" class="mt-2 text-xs text-rose-400">{{ form.errors.related_items }}</p>
                        <div class="mt-4 flex justify-center">
                            <button
                                class="rounded-xs bg-secondary px-4 py-2 text-xs text-text-primary transition hover:bg-secondary-hover"
                                type="button"
                                @click="addRelatedItem"
                            >
                                + Добавить связанное
                            </button>
                        </div>
                    </section>

                    <section class="rounded-xs border border-secondary/60 bg-accent p-5">
                        <div class="text-sm font-semibold text-text-primary">Кадры</div>
                        <label class="mt-4 flex flex-col items-center justify-center rounded-xs border border-dashed border-secondary bg-secondary/30 px-4 py-6 text-xs text-text-secondary">
                            <span>Перетащите файлы или <span class="text-text-primary">выберите</span></span>
                            <input class="hidden" type="file" accept="image/*" multiple @change="onFramesChange" />
                        </label>
                        <div v-if="framePreviews.length" class="mt-3 grid gap-3 sm:grid-cols-4">
                            <div
                                v-for="preview in framePreviews"
                                :key="preview.key"
                                class="group relative overflow-hidden rounded-xs"
                            >
                                <img :src="preview.url" alt="" class="h-20 w-full rounded-xs object-cover" />
                                <button
                                    class="absolute right-1 top-1 hidden rounded-xs bg-black/70 px-2 py-1 text-[10px] text-white group-hover:block"
                                    type="button"
                                    @click="removeFrame(preview)"
                                >
                                    Удалить
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.frames" class="mt-2 text-xs text-rose-400">{{ form.errors.frames }}</p>
                    </section>

                    <section class="rounded-xs border border-secondary/60 bg-accent p-5">
                        <div class="text-sm font-semibold text-text-primary">Трейлер</div>
                        <input
                            v-model="form.trailer_url"
                            class="mt-4 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            type="text"
                            placeholder="Ссылка на трейлер"
                        />
                        <p v-if="form.errors.trailer_url" class="mt-2 text-xs text-rose-400">{{ form.errors.trailer_url }}</p>
                    </section>

                    <div class="flex flex-wrap gap-3">
                        <button
                            class="rounded-xs bg-primary px-6 py-2 text-sm text-white transition hover:bg-primary-hover"
                            type="submit"
                            :disabled="form.processing"
                        >
                            {{ props.is_edit ? 'Сохранить' : 'Создать' }}
                        </button>
                        <Link
                            class="rounded-xs bg-secondary px-6 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                            href="/admin?section=anime"
                        >
                            Отмена
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <teleport to="body">
        <div
            v-if="showCharacterModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-6"
            @click.self="closeCharacterModal"
        >
            <div class="w-full max-w-[480px] rounded-xs bg-accent p-5 border border-secondary/60">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-text-primary">Добавить героя</div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                        type="button"
                        @click="closeCharacterModal"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs text-text-secondary">Имя героя</label>
                        <input
                            v-model="newCharacter.name"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            type="text"
                            placeholder="Имя героя"
                        />
                        <p v-if="newCharacterErrors.name" class="mt-2 text-xs text-rose-400">
                            {{ newCharacterErrors.name[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="text-xs text-text-secondary">Актер озвучки</label>
                        <input
                            v-model="newCharacter.voice_actor"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            type="text"
                            placeholder="Актер озвучки"
                        />
                        <p v-if="newCharacterErrors.voice_actor" class="mt-2 text-xs text-rose-400">
                            {{ newCharacterErrors.voice_actor[0] }}
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        class="rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                        type="button"
                        @click="closeCharacterModal"
                    >
                        Отмена
                    </button>
                    <button
                        class="rounded-xs bg-primary px-4 py-2 text-sm text-white transition hover:bg-primary-hover disabled:opacity-60"
                        type="button"
                        :disabled="newCharacterSaving"
                        @click="createCharacter"
                    >
                        Сохранить
                    </button>
                </div>
            </div>
        </div>
    </teleport>

    <teleport to="body">
        <div
            v-if="showStudioModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-6"
            @click.self="closeStudioModal"
        >
            <div class="w-full max-w-[480px] rounded-xs bg-accent p-5 border border-secondary/60">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-text-primary">Добавить студию</div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                        type="button"
                        @click="closeStudioModal"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs text-text-secondary">Название студии</label>
                        <input
                            v-model="newStudio.name"
                            class="mt-2 w-full rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            type="text"
                            placeholder="Название студии"
                        />
                        <p v-if="newStudioErrors.name" class="mt-2 text-xs text-rose-400">
                            {{ newStudioErrors.name[0] }}
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        class="rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                        type="button"
                        @click="closeStudioModal"
                    >
                        Отмена
                    </button>
                    <button
                        class="rounded-xs bg-primary px-4 py-2 text-sm text-white transition hover:bg-primary-hover disabled:opacity-60"
                        type="button"
                        :disabled="newStudioSaving"
                        @click="createStudio"
                    >
                        Сохранить
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

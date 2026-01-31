<script setup>
	import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
	import { Link, router } from '@inertiajs/vue3';

	const props = defineProps({
	    anime: {
	        type: Array,
	        required: true,
	    },
	    genres: {
	        type: Array,
	        default: () => [],
	    },
	    pagination: {
	        type: Object,
	        default: () => ({ current_page: 1, last_page: 1, has_more: false }),
	    },
	    filters: {
	        type: Object,
	        required: true,
	    },
	});

	const genreOptions = props.genres;

	const typeOptions = ['ТВ Сериал', 'Фильм', 'OVA', 'ONA', 'Спешл'];
	const statusOptions = ['Анонс', 'Онгоинг', 'Вышел'];
	const normalizeType = (value) =>
	    typeof value === 'string' ? value.trim().toLowerCase().replace(/\s+/g, ' ') : '';

	const typeLabels = {
	    tv: 'ТВ Сериал',
	    'tv сериал': 'ТВ Сериал',
	    'тв сериал': 'ТВ Сериал',
	    movie: 'Фильм',
	    film: 'Фильм',
	    фильм: 'Фильм',
	    ova: 'OVA',
	    ona: 'ONA',
	    спешл: 'Спешл',
	};

	const formatType = (value) => typeLabels[normalizeType(value)] || value || '-';
	const getPrimaryAltTitle = (value) => {
	    if (!value) return '-';
	    const first = value
	        .split(/\s*[|,;]\s*/)
	        .map((title) => title.trim())
	        .filter(Boolean)[0];
	    return first || '-';
	};

	const sortOptions = [
	    { value: 'created', label: 'Дате добавления' },
	    { value: 'title', label: 'Названию' },
	    { value: 'season', label: 'Дата выхода' },
	];

	const form = reactive({
	    q: props.filters.q ?? '',
	    sort: props.filters.sort ?? 'created',
	    dir: props.filters.dir ?? 'desc',
	    genres: Array.isArray(props.filters.genres) ? props.filters.genres : [],
	    type: props.filters.type ?? '',
	    status: props.filters.status ?? '',
	    year_from: props.filters.year_from ?? '',
	    year_to: props.filters.year_to ?? '',
	});

	const pendingFilters = reactive({
	    genres: [...form.genres],
	    type: form.type,
	    status: form.status,
	    year_from: form.year_from,
	    year_to: form.year_to,
	});

	const searchTimer = ref(null);
	const sortOpen = ref(false);
	const sortWrap = ref(null);
	const items = ref([...props.anime]);
	const pagination = ref({ ...props.pagination });
	const isAppending = ref(false);
	const isLoadingMore = ref(false);
	const loadMoreTrigger = ref(null);
	const observer = ref(null);

	const currentSortLabel = computed(() => {
	    const match = sortOptions.find((option) => option.value === form.sort);
	    return match ? match.label : 'Дате добавления';
	});

	const applyFilters = () => {
	    router.get('/anime', form, {
	        preserveState: true,
	        preserveScroll: true,
	    });
	};

	const applySidebarFilters = () => {
	    form.genres = [...pendingFilters.genres];
	    form.type = pendingFilters.type;
	    form.status = pendingFilters.status;
	    form.year_from = pendingFilters.year_from;
	    form.year_to = pendingFilters.year_to;
	    applyFilters();
	};

	const resetFilters = () => {
	    pendingFilters.genres = [];
	    pendingFilters.type = '';
	    pendingFilters.status = '';
	    pendingFilters.year_from = '';
	    pendingFilters.year_to = '';
	    applySidebarFilters();
	};

	const toggleSort = () => {
	    form.dir = form.dir === 'asc' ? 'desc' : 'asc';
	    applyFilters();
	};

	const toggleSortMenu = () => {
	    sortOpen.value = !sortOpen.value;
	};

	const selectSort = (value) => {
	    form.sort = value;
	    sortOpen.value = false;
	    applyFilters();
	};

	const handleSortOutside = (event) => {
	    if (!sortOpen.value) return;
	    const target = event.target;
	    if (sortWrap.value && !sortWrap.value.contains(target)) {
	        sortOpen.value = false;
	    }
	};

	watch(
	    () => props.anime,
	    (value) => {
	        if (isAppending.value) return;
	        items.value = [...value];
	    },
	);

	watch(
	    () => props.pagination,
	    (value) => {
	        if (isAppending.value) return;
	        pagination.value = { ...value };
	    },
	);

	watch(
	    () => form.q,
	    () => {
	        if (searchTimer.value) {
	            clearTimeout(searchTimer.value);
	        }
	        searchTimer.value = setTimeout(() => {
	            applyFilters();
	        }, 300);
	    },
	);

	const loadMore = () => {
	    if (isLoadingMore.value || !pagination.value.has_more) return;
	    isLoadingMore.value = true;
	    isAppending.value = true;
	    const nextPage = pagination.value.current_page + 1;
	    router.get(
	        '/anime',
	        { ...form, page: nextPage },
	        {
	            preserveState: true,
	            preserveScroll: true,
	            only: ['anime', 'pagination'],
	            onSuccess: (page) => {
	                const newItems = page.props.anime || [];
	                items.value = [...items.value, ...newItems];
	                pagination.value = page.props.pagination || pagination.value;
	            },
	            onFinish: () => {
	                isLoadingMore.value = false;
	                isAppending.value = false;
	            },
	        },
	    );
	};

	onMounted(() => {
	    document.addEventListener('click', handleSortOutside);
	    observer.value = new IntersectionObserver(
	        (entries) => {
	            if (entries[0]?.isIntersecting) {
	                loadMore();
	            }
	        },
	        { rootMargin: '200px' },
	    );
	    if (loadMoreTrigger.value) {
	        observer.value.observe(loadMoreTrigger.value);
	    }
	});

	onBeforeUnmount(() => {
	    document.removeEventListener('click', handleSortOutside);
	    if (observer.value && loadMoreTrigger.value) {
	        observer.value.unobserve(loadMoreTrigger.value);
	    }
	});
</script>

<template>
	<div class="w-full">
		<div class="max-w-[1440px] mx-auto p-5">
			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_390px] lg:items-start">
				<div class="rounded bg-accent p-5 pb-0 border border-secondary/60">
					<h1 class="text-2xl font-semibold">Каталог аниме</h1>

					<form class="mt-4 flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
						<div class="flex flex-1 items-center gap-2 rounded-xs bg-secondary px-3 py-2">
							<svg v-if="!showSearch" id="searchIcon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M14.3851 15.4457C11.7349 17.5684 7.85544 17.4013 5.39866 14.9445C2.76262 12.3085 2.76262 8.03464 5.39866 5.3986C8.0347 2.76256 12.3086 2.76256 14.9446 5.3986C17.4014 7.85538 17.5685 11.7348 15.4458 14.3851L20.6014 19.5407C20.8943 19.8336 20.8943 20.3085 20.6014 20.6014C20.3085 20.8943 19.8337 20.8943 19.5408 20.6014L14.3851 15.4457ZM6.45932 13.8839C4.40907 11.8336 4.40907 8.50951 6.45932 6.45926C8.50957 4.40901 11.8337 4.40901 13.8839 6.45926C15.9327 8.50801 15.9342 11.8287 13.8885 13.8794C13.8869 13.8809 13.8854 13.8823 13.8839 13.8839C13.8824 13.8854 13.8809 13.8869 13.8794 13.8884C11.8288 15.9341 8.50807 15.9326 6.45932 13.8839Z"
									fill="#bfbfbf"
								/>
							</svg>
							<input v-model="form.q" class="w-full bg-transparent text-sm text-text-primary focus:outline-none" placeholder="Поиск по названию" type="text" />
						</div>

						<div class="flex items-center gap-2">
							<div ref="sortWrap" class="relative">
								<button class="flex min-w-[200px] cursor-pointer items-center gap-2 rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary transition hover:bg-secondary-hover" type="button" @click.stop="toggleSortMenu">
									<svg width="20" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
										<path d="M4 6h16M4 12h10M4 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
									</svg>
									<span class="flex-1 text-left">{{ currentSortLabel }}</span>
									<svg class="ml-auto" width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
										<path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</button>
								<div v-if="sortOpen" class="absolute right-0 z-30 mt-2 min-w-[200px] rounded-xs bg-secondary p-2 shadow-lg">
									<button
										v-for="option in sortOptions.filter((item) => item.value !== form.sort)"
										:key="option.value"
										class="flex cursor-pointer transition hover:bg-secondary-hover w-full items-center rounded-xs px-3 py-2 text-left text-sm text-text-primary transition hover:bg-secondary"
										type="button"
										@click="selectSort(option.value)"
									>
										{{ option.label }}
									</button>
								</div>
							</div>
							<button class="rounded-xs bg-secondary px-3 py-2 text-md text-text-primary transition cursor-pointer hover:bg-secondary-hover" type="button" @click="toggleSort">
								⇅
							</button>
						</div>
					</form>

					<div class="mt-6 space-y-4">
						<div v-for="item in items" :key="item.id" class="">
							<Link class="flex flex-wrap gap-4" :href="`/anime/${item.id}`">
								<div class="h-40 w-28 overflow-hidden rounded-xs bg-input">
									<img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" class="h-full w-full object-cover" />
								</div>
								<div class="flex-1">
									<h2 class="text-lg font-semibold text-text-primary">{{ item.title }}</h2>
									<p class="text-sm italic text-text-secondary">{{ getPrimaryAltTitle(item.alt_title) }}</p>
									<p class="mt-2 text-sm text-text-primary">
										{{ formatType(item.type) }} | {{ item.season_label || item.year || '-' }} | {{ item.genres || '-' }}
									</p>
									<p class="mt-2 text-sm text-text-secondary truncate-3">
										{{ item.description || 'Описание отсутствует.' }}
									</p>
								</div>
							</Link>
						</div>

						<div v-if="!items.length" class="rounded-xs bg-secondary p-6 text-sm text-text-secondary">
							Результаты отсутствуют.
						</div>
						<div v-if="isLoadingMore" class="flex items-center justify-center gap-3 py-2 text-sm text-text-secondary">
							<span class="catalog-spinner h-6 w-6"></span>
							Загружаем еще...
						</div>
						<div ref="loadMoreTrigger" class="h-1"></div>
					</div>
				</div>

				<aside class="sticky top-20 rounded bg-accent p-5 border border-secondary/60 lg:shrink-0 lg:w-[390px] lg:self-start lg:h-fit">
					<h2 class="text-2xl font-semibold">Фильтр</h2>

					<div class="mt-4 space-y-6 text-sm">
						<div>
							<div class="text-text-primary">Жанр</div>
							<div class="mt-3 grid grid-cols-2 gap-2">
								<label v-for="genre in genreOptions" :key="genre" class="flex items-center gap-2 cursor-pointer text-text-secondary">
									<input v-model="pendingFilters.genres" :value="genre" class="filter-checkbox cursor-pointer" type="checkbox" />
									{{ genre }}
								</label>
							</div>
						</div>

						<div>
							<div class="text-text-primary">Год релиза</div>
							<div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
								<input v-model="pendingFilters.year_from" class="min-w-0 rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary focus:outline-none no-spinner" placeholder="От" type="number" />
								<span class="text-text-secondary">—</span>
								<input v-model="pendingFilters.year_to" class="min-w-0 rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary focus:outline-none no-spinner" placeholder="До" type="number" />
							</div>
						</div>

						<div>
							<div class="text-text-primary">Тип</div>
							<div class="mt-3 grid grid-cols-2 gap-2">
								<label v-for="type in typeOptions" :key="type" class="flex items-center gap-2 cursor-pointer text-text-secondary">
									<input v-model="pendingFilters.type" :value="type" class="filter-radio cursor-pointer" type="radio" name="type" />
									{{ type }}
								</label>
							</div>
						</div>

						<div>
							<div class="text-text-primary">Статус</div>
							<div class="mt-3 grid grid-cols-2 gap-2">
								<label v-for="status in statusOptions" :key="status" class="flex items-center gap-2 cursor-pointer text-text-secondary">
									<input v-model="pendingFilters.status" :value="status" class="filter-radio cursor-pointer" type="radio" name="status" />
									{{ status }}
								</label>
							</div>
						</div>

						<div class="mt-4 flex items-center gap-3">
							<button class="flex-1 rounded-xs bg-secondary px-4 py-2 text-sm text-text-primary transition cursor-pointer hover:bg-secondary-hover" type="button" @click="resetFilters">
								Сбросить
							</button>
							<button class="flex-1 rounded-xs bg-primary px-4 py-2 text-sm text-white transition cursor-pointer hover:bg-primary-hover" type="button" @click="applySidebarFilters">
								Применить
							</button>
						</div>
					</div>
				</aside>
			</div>
		</div>
	</div>
</template>

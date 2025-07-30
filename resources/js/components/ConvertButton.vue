<script setup>
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-vue-next';

defineProps({
	disabled: {
		type: Boolean,
		default: false,
	},
	loading: {
		type: Boolean,
		default: false,
	},
});

const emit = defineEmits(['convert']);

const handleConvert = () => {
	emit('convert');
};
</script>

<template>
	<div>
		<Button @click="handleConvert" :disabled="disabled || loading" class="min-h-[44px] w-full sm:min-h-[48px]" size="lg">
			<template v-if="loading">
				<svg
					class="mr-2 -ml-1 h-4 w-4 animate-spin text-white sm:mr-3 sm:h-5 sm:w-5"
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 24 24"
				>
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path
						class="opacity-75"
						fill="currentColor"
						d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
					></path>
				</svg>
				<span class="text-sm sm:text-base">Конвертация...</span>
			</template>
			<template v-else>
				<Download class="mr-2 h-4 w-4 sm:h-5 sm:w-5" />
				<span class="text-sm sm:text-base">Конвертировать в PDF</span>
			</template>
		</Button>

		<p class="mt-3 px-2 text-center text-xs text-muted-foreground sm:text-sm">Ваш конвертированный PDF будет загружен автоматически</p>
	</div>
</template>

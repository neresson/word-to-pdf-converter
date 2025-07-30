<script setup>
import { AlertCircle } from 'lucide-vue-next';

defineProps({
	error: {
		type: String,
		default: null,
	},
});
</script>

<template>
	<div v-if="error" class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/50">
		<div class="flex">
			<AlertCircle class="h-5 w-5 text-red-400" />
			<div class="ml-3">
				<p class="text-sm text-red-700 dark:text-red-300">{{ error }}</p>
			</div>
		</div>
	</div>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, X } from 'lucide-vue-next';

const props = defineProps({
	modelValue: {
		type: Array,
		default: () => [],
	},
});

const emit = defineEmits(['update:modelValue']);

const addTextReplacement = () => {
	const newReplacements = [
		...props.modelValue,
		{
			id: Date.now().toString(),
			search: '',
			replace: '',
		},
	];
	emit('update:modelValue', newReplacements);
};

const removeTextReplacement = (id) => {
	const newReplacements = props.modelValue.filter((tr) => tr.id !== id);
	emit('update:modelValue', newReplacements);
};

const updateReplacement = (id, field, value) => {
	const newReplacements = props.modelValue.map((tr) => (tr.id === id ? { ...tr, [field]: value } : tr));
	emit('update:modelValue', newReplacements);
};
</script>

<template>
	<div class="space-y-3">
		<div v-for="(replacement, index) in modelValue" :key="replacement.id" class="rounded-md bg-gray-50 p-3 sm:p-4 dark:bg-gray-700">
			<!-- Mobile Layout (Stacked) -->
			<div class="block space-y-3 sm:hidden">
				<div class="space-y-2">
					<Label class="text-xs font-medium"> Найти этот текст: </Label>
					<Input
						:model-value="replacement.search"
						@update:model-value="updateReplacement(replacement.id, 'search', $event)"
						type="text"
						placeholder="Текст для поиска"
						class="text-sm"
					/>
				</div>
				<div class="space-y-2">
					<Label class="text-xs font-medium"> Заменить на: </Label>
					<Input
						:model-value="replacement.replace"
						@update:model-value="updateReplacement(replacement.id, 'replace', $event)"
						type="text"
						placeholder="Замещающий текст"
						class="text-sm"
					/>
				</div>
				<div class="flex justify-end gap-2">
					<Button v-if="index !== 0" @click="removeTextReplacement(replacement.id)" variant="outline" size="sm" class="px-3">
						<X class="mr-1 h-4 w-4" />
						Удалить
					</Button>

					<Button v-if="index === modelValue.length - 1" @click="addTextReplacement" variant="outline" size="sm" class="px-3">
						<Plus class="mr-1 h-4 w-4" />
						Добавить
					</Button>
				</div>
			</div>

			<!-- Desktop Layout (Grid) -->
			<div class="hidden sm:block">
				<!-- Labels Row -->
				<div class="mb-2 grid grid-cols-[1fr_1fr_80px] gap-2">
					<Label class="text-xs"> Найти этот текст: </Label>
					<Label class="text-xs"> Заменить на: </Label>
					<div></div>
					<!-- Empty space for button alignment -->
				</div>

				<!-- Inputs and Buttons Row -->
				<div class="grid grid-cols-[1fr_1fr_80px] items-center gap-2">
					<Input
						:model-value="replacement.search"
						@update:model-value="updateReplacement(replacement.id, 'search', $event)"
						type="text"
						placeholder="Текст для поиска и замены"
						size="sm"
					/>
					<Input
						:model-value="replacement.replace"
						@update:model-value="updateReplacement(replacement.id, 'replace', $event)"
						type="text"
						placeholder="Замещающий текст"
						size="sm"
					/>
					<div class="flex justify-end gap-1">
						<Button v-if="index !== 0" @click="removeTextReplacement(replacement.id)" variant="outline" size="icon" title="Удалить замену текста">
							<X class="h-4 w-4" />
						</Button>
						<!-- Invisible placeholder button to maintain consistent spacing -->
						<div v-else class="h-9 w-9"></div>

						<Button v-if="index === modelValue.length - 1" @click="addTextReplacement" variant="outline" size="icon" title="Добавить замену текста">
							<Plus class="h-4 w-4" />
						</Button>
						<!-- Invisible placeholder button to maintain consistent spacing -->
						<div v-else class="h-9 w-9"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

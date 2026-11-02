<x-layouts::public :title="__('Unsubscribed')">
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 sm:p-12 shadow-sm">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mb-6">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">You have been unsubscribed</h1>
            <p class="text-gray-600 text-sm sm:text-base mb-8">
                You will no longer receive email notifications when new posts are published. We are sorry to see you go!
            </p>

            <a
                href="{{ route('blog.index') }}"
                wire:navigate
                class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium text-sm rounded-xl hover:bg-indigo-700 transition shadow-sm"
            >
                ← Back to Blog
            </a>
        </div>
    </div>
</x-layouts::public>

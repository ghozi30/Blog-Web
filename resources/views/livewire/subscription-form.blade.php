<div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-purple-900 rounded-2xl shadow-xl p-8 sm:p-12 text-white relative overflow-hidden">
    <!-- Background glow decoration -->
    <div class="absolute top-0 right-0 -mt-12 -mr-12 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-2xl mx-auto text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-white/10 rounded-xl mb-4 backdrop-blur-sm">
            <svg class="w-6 h-6 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Stay updated with our newsletter</h2>
        <p class="mt-2 text-indigo-200 text-sm sm:text-base">
            Get the latest articles, tutorials, and development tips delivered directly to your inbox.
        </p>

        @if($subscribed)
            <div class="mt-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-100 flex items-center justify-center gap-3">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Thank you for subscribing! You will receive an email whenever a new post is published.</span>
            </div>
        @else
            <form wire:submit="subscribe" class="mt-6 sm:flex sm:max-w-md sm:mx-auto gap-3">
                <div class="flex-1 min-w-0">
                    <label for="subscriber-email" class="sr-only">Email address</label>
                    <input
                        id="subscriber-email"
                        type="email"
                        wire:model="email"
                        placeholder="Enter your email address"
                        class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white focus:border-transparent text-sm backdrop-blur-sm"
                        required
                    />
                </div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="mt-3 sm:mt-0 w-full sm:w-auto px-6 py-3 bg-white text-indigo-900 font-semibold rounded-xl hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white text-sm transition shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                    <span wire:loading wire:target="subscribe" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-indigo-900" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Subscribing...
                    </span>
                </button>
            </form>

            @error('email')
                <p class="mt-2 text-rose-300 text-xs sm:text-sm font-medium">{{ $message }}</p>
            @enderror
        @endif
    </div>
</div>

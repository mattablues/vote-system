      <div
        x-show="openDeleteModal"
        x-cloak
        x-on:keydown.escape.window="openDeleteModal = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div x-show="openDeleteModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
        <div
          x-show="openDeleteModal" x-transition
          x-on:click="openDeleteModal = false"
          class="relative flex min-h-screen items-center justify-center p-4"
        >
          <div
            x-on:click.stop
            class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
          >
            <h2 class="text-xl font-semibold text-gray-800" :id="$id('modal-title')">
              Radera konto
            </h2>

            <p class="mt-3 text-sm text-gray-700">
              Ditt konto kommer att raderas och all din lagrade information kommer att försvinna. Om du inte vill radera ditt innehåll
              kan du istället stänga kontot, och vid ett senare tillfälle öppna det igen.
            </p>
            <p class="mt-1 text-sm font-medium text-gray-700">
              Är du säker på att du vill fortsätta och radera ditt konto?
            </p>

            <form action="{{ route('user.delete') }}" method="post" class="mt-5 flex justify-end gap-2">
              {{ csrf_field()|raw }}
              <button
                type="reset"
                x-on:click="openDeleteModal = false"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Avbryt
              </button>

              <button
                x-on:click="openDeleteModal = false"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700 transition-colors cursor-pointer"
              >
                Radera
              </button>
            </form>
          </div>
        </div>
      </div>
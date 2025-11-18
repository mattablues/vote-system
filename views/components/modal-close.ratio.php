      <div
        x-show="openCloseModal"
        x-cloak
        x-on:keydown.escape.window="openCloseModal = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div x-show="openCloseModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
        <div
          x-show="openCloseModal" x-transition
          x-on:click="openCloseModal = false"
          class="relative flex min-h-screen items-center justify-center p-4"
        >
          <div
            x-on:click.stop
            class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
          >
            <h2 class="text-xl font-semibold text-gray-800" :id="$id('modal-title')">
              Stäng konto
            </h2>

            <p class="mt-3 text-sm text-gray-700">
              Ditt konto kommer att stängas och du kommer inte kunna logga in igen. Om du vill aktivera kontot igen,
              kontakta support från din registrerade e-postadress så hjälper vi dig.
            </p>
            <p class="mt-1 text-sm font-medium text-gray-700">
              Är du säker på att du vill fortsätta och stänga ditt konto?
            </p>

            <form action="{{ route('user.close') }}" method="post" class="mt-5 flex justify-end gap-2">
              {{ csrf_field()|raw }}
              <button
                type="reset"
                x-on:click="openCloseModal = false"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Avbryt
              </button>

              <button
                x-on:click="openCloseModal = false"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700 transition-colors cursor-pointer"
              >
                Stäng konto
              </button>
            </form>
          </div>
        </div>
      </div>
{% extends "layouts/admin.ratio.php" %}
{% block title %}Stängda konton{% endblock %}
{% block pageId %}user-closed{% endblock %}
{% block searchId %}search-deleted-users{% endblock %}
{% block body %}
        <section x-data="{ openClosedModal: null, selectedUser: { id: null, email: '' } }">
          <div class="flex items-start justify-between gap-4 mb-6">
            <h1 class="text-3xl font-semibold">Stängda konton</h1>
            <div class="text-xs text-gray-600 hidden md:block mt-2 font-medium">
              {{ $users['pagination']['total'] ?? 0 }} totalt
            </div>
          </div>
{% if($users['data']) : %}
          <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead class="bg-gray-50/80">
                  <tr class="border-b border-gray-200">
                    <th data-cell="id" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600 max-md:hidden">ID</th>
                    <th data-cell="namn" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Namn</th>
                    <th data-cell="e-post" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">E‑postadress</th>
                    <th data-cell="status" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600 max-sm:hidden">Status</th>
                    <th data-cell="aktiv" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600 max-sm:hidden">Aktiv</th>
                    <th data-cell="åtgärd" class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">Åtgärd</th>
                  </tr>
                </thead>
                <tbody class="[&_tr:nth-child(odd)]:bg-white [&_tr:nth-child(even)]:bg-gray-50">
{% foreach($users['data'] as $user) : %}
                  <tr class="[&:not(:last-child)]:border-b border-gray-200 hover:bg-blue-50/50 transition-colors">
                    <td data-cell="id" class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap max-md:hidden">{{ $user->getAttribute('id') }}</td>
                    <td data-cell="namn" class="px-3 py-2.5 text-sm">
                      <a href="{{ route('user.show', ['id' => $user->getAttribute('id')]) }}" class="font-medium text-gray-900 underline decoration-gray-300 hover:decoration-transparent">
                        {{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}
                      </a>
                    </td>
                    <td data-cell="e-post" class="px-3 py-2.5 text-sm text-gray-700">
                      <span class="break-all">{{ $user->getAttribute('email') }}</span>
                    </td>
                    <td data-cell="status" class="px-3 py-2.5 text-sm max-sm:hidden">
                      <div class="inline-flex items-center text-xs py-0.5 text-gray-700">
                        <span class="{{ $user->getRelation('status')->getAttribute('status') }}">
                          {{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}
                        </span>
                      </div>
                    </td>
                    <td data-cell="aktiv" class="px-3 py-2.5 text-sm max-sm:hidden">
                      <div class="inline-flex items-center text-xs py-0.5 text-gray-700">
                        <span class="{{ $user->getRelation('status')->getAttribute('active') }}">{{ $user->getRelation('status')->getAttribute('active') }}</span>
                      </div>
                    </td>
                    <td data-cell="åtgärd" class="px-3 py-2.5 text-sm">
                      <div class="flex flex-wrap items-center gap-2">
                        <button
                          type="button"
                          x-on:click="selectedUser = { id: {{ $user->getAttribute('id') }}, email: '{{ addslashes($user->getAttribute('email')) }}' }; openClosedModal = true"
                          class="inline-flex items-center text-xs font-semibold bg-green-600 text-white py-1 px-2 rounded hover:bg-green-700 active:bg-green-800 transition-colors whitespace-nowrap cursor-pointer"
                        >
                          Återställ
                        </button>
                      </div>
                    </td>
                  </tr>
{% endforeach; %}
                </tbody>
              </table>
            </div>
          </div>
          <!-- Modal -->
          <div
            x-show="openClosedModal"
            x-cloak
            x-on:keydown.escape.window="openClosedModal = false"
            role="dialog"
            aria-modal="true"
            x-id="['modal-title']"
            :aria-labelledby="$id('modal-title')"
            class="fixed inset-0 z-50 overflow-y-auto"
          >
            <div x-show="openClosedModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
            <div
              x-show="openClosedModal" x-transition
              x-on:click="openClosedModal = false"
              class="relative flex min-h-screen items-center justify-center p-4"
            >
              <div
                x-on:click.stop
                class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
              >
                <h2 class="text-xl font-semibold text-gray-800" :id="$id('modal-title')">
                  Återställ konto
                </h2>
                <p class="mt-3 text-sm text-gray-700">
                  Detta kommer att återställa kontot <strong x-text="selectedUser.email"></strong> och skicka en aktiveringslänk.
                </p>
                <p class="mt-1 text-sm font-medium text-gray-700">Är du säker på att du vill fortsätta?</p>

                <div class="mt-5 flex justify-end gap-2">
                  <button type="button" x-on:click="openClosedModal = false" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors cursor-pointer">
                    Avbryt
                  </button>
                  <form x-bind:action="'{{ route('admin.user.restore', ['id' => '__ID__']) }}?page={{ $users['pagination']['current_page'] }}'.replace('__ID__', selectedUser.id)" method="post">
                    {{ csrf_field()|raw }}
                    <button type="submit" x-on:click="openClosedModal = false" class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700 transition-colors cursor-pointer">
                      Återställ
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <!-- End Modal -->
{% if($users['pagination']['total'] > $users['pagination']['per_page']) : %}
          <div class="flex flex-wrap items-center justify-between gap-3 mt-1 mb-3">
            <span class="block text-xs font-medium text-gray-600">{{ $users['pagination']['total'] }} totalt</span>
            <span class="block text-xs font-medium text-gray-600">Sida {{ $users['pagination']['current_page'] }} av {{ calculate_total_pages($users['pagination']['total'], $users['pagination']['per_page']) }}</span>
          </div>
          <div class="mt-2">
            {{ paginate_links($users['pagination'], 'admin.user.closed', 2)|raw }}
          </div>
{% endif; %}
{% else : %}
          <p>Inga stängda konton hittades.</p>
{% endif; %}
        </section>
{% endblock %}
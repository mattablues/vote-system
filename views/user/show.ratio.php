{% extends "layouts/admin.ratio.php" %}
{% block title %}Konto{% endblock %}
{% block pageId %}show-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
        <section x-data="{ openRoleModal: false, selectedRole: '{{ $user ? $user->fetchGuardedAttribute('role') : null }}' }">
          <h1 class="text-3xl font-semibold mb-8">Konto</h1>
{% if($user) : %}
          <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

          <div class="w-full max-w-2xl">
            <div class="flex flex-col sm:flex-row items-stretch gap-4 border border-gray-200 rounded-xl bg-white/70 backdrop-blur-sm">
              <figure class="flex items-center justify-center rounded-t-xl sm:rounded-t-none sm:rounded-l-xl sm:justify-start p-4 bg-gray-50">
                <div class="relative">
                  <img src="{{ versioned_file($user->getAttribute('avatar')) }}" alt="Avatar" class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover ring-2 ring-gray-100">
                  <span class="sr-only">Profilbild</span>
                </div>
              </figure>
              <div class="flex-1 p-4 sm:px-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-3">
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Namn</dt>
                    <dd class="text-sm text-gray-900">
                      {{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}
                    </dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">E‑post</dt>
                    <dd class="text-sm text-gray-900 break-all">{{ $user->getAttribute('email') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Skapad</dt>
                    <dd class="text-sm text-gray-900">{{ $user->getAttribute('created_at') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Senast aktiv</dt>
                    <dd class="text-sm text-gray-900">
                    {% if($user->getRelation('status')->getAttribute('active_at')) : %}
                      {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}
                    {% else : %}
                      aldrig
                    {% endif; %}
                    </dd>
                  </dl>
                {% if($currentUser->hasAtLeast('moderator')) : %}
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Kontostatus</dt>
                    <dd class="text-sm text-gray-900">
                      <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                        <span class="text-{{ $user->getRelation('status')->getAttribute('status') }}">
                          {{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}
                        </span>
                      </span>
                    </dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Behörighet</dt>
                    <dd class="text-sm text-gray-900">{{ $user->fetchGuardedAttribute('role') }}</dd>
                  </dl>
                {% endif; %}
                </div>
              </div>
            </div>

          {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
            <div class="flex flex-wrap gap-2 justify-end px-1 sm:px-0 mt-3">
              <button
                type="button"
                x-on:click="openRoleModal = true"
                class="inline-flex items-center text-sm border border-transparent bg-blue-600 px-3 py-1 text-white hover:bg-blue-700 active:bg-blue-800 transition-colors rounded-md cursor-pointer"
              >
                Ändra behörighet
              </button>
            </div>
          {% endif; %}
          </div>

          {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
          <div
            x-show="openRoleModal"
            x-cloak
            x-on:keydown.escape.window="openRoleModal = false"
            role="dialog"
            aria-modal="true"
            x-id="['modal-title']"
            :aria-labelledby="$id('modal-title')"
            class="fixed inset-0 z-50 overflow-y-auto"
          >
            <div x-show="openRoleModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
            <div
              x-show="openRoleModal" x-transition
              x-on:click="openRoleModal = false"
              class="relative flex min-h-screen items-center justify-center p-4"
            >
              <div
                x-on:click.stop
                class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
              >
                <h2 class="text-xl font-semibold text-gray-800" :id="$id('modal-title')">
                  Ändra behörighet
                </h2>

                <p class="mt-3 text-sm text-gray-700">
                  Välj en ny behörighet för kontot <strong>{{ $user->getAttribute('email') }}</strong>.
                </p>

                <form action="{{ route('admin.user.role', ['id' => $user->getAttribute('id')]) }}" method="post" class="mt-4">
                  {{ csrf_field()|raw }}
                  <label for="role" class="block text-sm text-slate-600 mb-1 sr-only">Behörighet</label>
                  <select
                    id="role"
                    name="role"
                    x-model="selectedRole"
                    class="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                    required
                  >
                  {% foreach ($roles as $roleCase): %}
                    {% if($roleCase->value !== 'admin') : %}
                    <option value="{{ $roleCase->value }}">{{ $roleCase->value }}</option>
                  {% endif; %}
                  {% endforeach; %}
                  </select>

                  <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="openRoleModal = false" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors cursor-pointer">
                      Avbryt
                    </button>
                    <button type="submit" x-on:click="openRoleModal = false" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700 transition-colors cursor-pointer">
                      Spara
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          {% endif; %}
{% else : %}
          <p>Konto hittades inte.</p>
{% endif; %}
        </section>
{% endblock %}
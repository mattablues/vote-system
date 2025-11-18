{% extends "layouts/admin.ratio.php" %}
{% block title %}Visa konto{% endblock %}
{% block pageId %}user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
        <section>
          <h1 class="text-3xl font-semibold mb-8">Konto</h1>

          <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

          <div class="w-full max-w-2xl">
            <div class="flex flex-col sm:flex-row items-stretch gap-4 border border-gray-200 rounded-xl bg-white/70 backdrop-blur-sm">
              <figure class="flex items-center justify-center rounded-t-xl sm:rounded-t-none sm:rounded-l-xl sm:justify-start p-4 bg-gray-50">
                <div class="relative">
                  <img src="{{ versioned_file($currentUser->getAttribute('avatar')) }}" alt="Avatar" class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover ring-2 ring-gray-100">
                  <span class="sr-only">Profilbild</span>
                </div>
              </figure>
              <div class="flex-1 p-4 sm:px-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-3">
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Namn</dt>
                    <dd class="text-sm text-gray-900">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">E‑post</dt>
                    <dd class="text-sm text-gray-900 break-all">{{ $currentUser->getAttribute('email') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Behörighet</dt>
                    <dd class="text-sm text-gray-900">{{ $currentUser->fetchGuardedAttribute('role') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Skapat</dt>
                    <dd class="text-sm text-gray-900">{{ $currentUser->getAttribute('created_at') }}</dd>
                  </dl>
                  <dl>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Uppdaterat</dt>
                    <dd class="text-sm text-gray-900">{{ $currentUser->getAttribute('updated_at') }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 justify-end px-1 sm:px-0 mt-3">
              <a href="{{ route('user.edit') }}" class="inline-flex items-center text-sm border border-transparent bg-blue-600 px-3 py-1 text-white hover:bg-blue-700 active:bg-blue-800 transition-colors rounded-md">
                Redigera
              </a>
            </div>
          </div>
        </section>
{% endblock %}
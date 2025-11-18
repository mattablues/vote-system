{% extends "layouts/admin.ratio.php" %}
{% block title %}Röstberättigade{% endblock %}
{% block pageId %}voters{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openBlockModal: null, selectedVoter: { id: null, email: '' } }">
      <div class="flex items-start justify-between gap-4 mb-6">
        <h1 class="text-3xl font-semibold">Röstberättigade</h1>
        <div class="text-xs text-gray-600 font-medium hidden md:block mt-3">
          {{ $voters['pagination']['total'] ?? 0 }} totalt
        </div>
      </div>
{% if($voters['data']) : %}
      <table class="w-full">
        <thead>
          <tr class="text-left border-b border-t border-gray-200">
            <th data-cell="id" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">ID</th>
            <th data-cell="e-post" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">E-postadress</th>
            <th data-cell="status" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Status</th>
            <th data-cell="röster" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Röster</th>
            <th data-cell="åtgärd" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Åtgärd</th>
          </tr>
        </thead>
        <tbody>
{% foreach($voters['data'] as $voter) : %}
          <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
            <td data-cell="id" class="px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $voter->getAttribute('id') }}</td>
            <td data-cell="e-post" class="w-full px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $voter->getAttribute('email') }}</td>
            <td data-cell="status" class="px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><div class="flex items-center text-xs">
              <div class="inline-flex items-center text-xs py-0.5 text-gray-700">
                <span class="{{ $voter->getAttribute('status') }}">{{ $voter->translateStatus($voter->getAttribute('status')) }}</span>
              </div>
            </td>
            <td data-cell="röster" class="w-full px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $voter->getAttribute('vote_count') }}</td>
            <td data-cell="åtgärd" class="ml-auto px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
              <div class="flex items-center gap-1.5 pb-1.5 md:pb-0">
                <form action="{{ route('admin.voter.send-activation', ['id' => $voter->getAttribute('id')]) }}?page={{ $voters['pagination']['current_page'] }}" method="post">
                  {{ csrf_field()|raw }}
                  <button class="inline-flex items-center text-xs font-semibold bg-blue-600 text-white py-1 px-2 rounded hover:bg-blue-700 active:bg-blue-800 transition-colors cursor-pointer">Aktivering</button>
                </form>

{% if($voter->getAttribute('status') !== 'blocked') : %}
                <button
                  type="button"
                  x-on:click="selectedVoter = { id: {{ $voter->getAttribute('id') }}, email: '{{ addslashes($voter->getAttribute('email')) }}' }; openBlockModal = true"
                  class="inline-flex items-center text-xs font-semibold bg-red-600 text-white py-1 px-2 rounded hover:bg-red-700 active:bg-red-800 transition-colors whitespace-nowrap cursor-pointer"
                >
                  Blockera
                </button>
{% else : %}
                <span class="inline-block text-xs font-semibold bg-gray-200/70 text-gray-400 py-1 px-2 rounded cursor-not-allowed">Blockera</span>
{% endif; %}
              </div>
            </td>
          </tr>
{% endforeach; %}
        </tbody>
      </table>
      <div
        x-show="openBlockModal"
        x-cloak
        x-on:keydown.escape.window="openBlockModal = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div x-show="openBlockModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
        <div
          x-show="openBlockModal" x-transition
          x-on:click="openBlockModal = false"
          class="relative flex min-h-screen items-center justify-center p-4"
        >
          <div
            x-on:click.stop
            class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
          >
            <h2 class="text-xl font-semibold text-gray-800" :id="$id('modal-title')">
              Blockera röstberättigad
            </h2>

            <p class="mt-3 text-sm text-gray-700">
              Detta kommer att blockera röstberättigad <strong x-text="selectedVoter.email"></strong>.
            </p>

            <p class="mt-1 text-sm font-medium text-gray-700">Är du säker på att du vill fortsätta?</p>

            <div class="mt-5 flex justify-end gap-2">
              <button type="button" x-on:click="openBlockModal = false" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors">
                Avbryt
              </button>
              <form x-bind:action="'{{ route('admin.voter.block', ['id' => '__ID__']) }}?page={{ $voters['pagination']['current_page'] }}'.replace('__ID__', selectedVoter.id)" method="post">
                {{ csrf_field()|raw }}

                <button type="submit" x-on:click="openBlockModal = false" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700 transition-colors">
                  Blockera
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- End Modal -->
{% if($voters['pagination']['total'] > $voters['pagination']['per_page']) : %}
      <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
        <span class="block text-xs font-medium text-gray-600">{{ $voters['pagination']['total'] }} totalt</span>
        <span class="block text-xs font-medium text-gray-600">sida {{ $voters['pagination']['current_page'] }} av {{ calculate_total_pages($voters['pagination']['total'], $voters['pagination']['per_page']) }}</span>
      </div>
      <div class="mt-3">
        {{ paginate_links($voters['pagination'], 'admin.voter.index', 2)|raw }}
      </div>
{% endif; %}
{% else : %}
      <p>Inga konton hittades.</p>
{% endif; %}
    </section>
{% endblock %}
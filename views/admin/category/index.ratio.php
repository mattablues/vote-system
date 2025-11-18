{% extends "layouts/admin.ratio.php" %}
{% block title %}Kategorier{% endblock %}
{% block pageId %}categories{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openDeleteModal: null }">
      <div class="flex items-start justify-between gap-4 mb-6">
        <h1 class="text-3xl font-semibold">Kategorier</h1>
        <div class="text-xs text-gray-600 font-medium hidden md:block mt-3">
          {{ $categories['pagination']['total'] ?? 0 }} totalt
        </div>
      </div>
{% if($categories['data']) : %}
      <table class="w-full">
        <thead>
          <tr class="text-left border-b border-gray-200">
            <th data-cell="kategori" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Kategori</th>
            <th data-cell="beskrivning" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Beskrivning</th>
            <th data-cell="åtgärd" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Åtgärd</th>
          </tr>
        </thead>
        <tbody>
{% foreach($categories['data'] as $category) : %}
          <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
            <td data-cell="kategori" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm font-semibold max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $category->getAttribute('category') }}</td>
            <td data-cell="beskrivning" class="w-full  md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $category->getAttribute('description') }}</td>
            <td data-cell="åtgärd" class="ml-auto px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
              <div class="flex items-center gap-1.5 pb-1.5 md:pb-0">
                <a href="{{ route('admin.category.edit', ['id' => $category->getAttribute('id')]) }}" class="inline-flex items-center text-xs font-semibold bg-blue-600 text-white py-1 px-2 rounded hover:bg-blue-700 active:bg-blue-800 transition-colors cursor-pointer">Redigera</a>
{% if($currentUser->hasAtLeast('moderator')) : %}
                <button type="button" x-on:click="openDeleteModal = true" class="inline-flex items-center text-xs font-semibold bg-red-600 text-white py-1 px-2 rounded hover:bg-red-700 active:bg-red-800 transition-colors whitespace-nowrap cursor-pointer">Ta bort</button>
{% endif; %}
              </div>
            </td>
          </tr>
{% endforeach; %}
        </tbody>
      </table>
{% if($currentUser->hasAtLeast('moderator')) : %}
      <!-- Modal -->
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
              Ta bort kategori
            </h2>

            <p class="mt-3 text-sm text-gray-700">
              Detta kommer att ta bort Kategorin associerade ämnen och röster. Åtgärden kan inte ångras.
            </p>
            <p class="mt-1 text-sm font-medium text-gray-700">Är du säker på att du vill fortsätta?</p>

            <div class="mt-5 flex justify-end gap-2">
              <button type="button" x-on:click="openDeleteModal = false" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors">
                  Avbryt
              </button>
              <form action="{{ route('admin.category.delete', ['id' => $category->getAttribute('id')]) }}" method="post">
                {{ csrf_field()|raw }}
                <button type="submit" x-on:click="openDeleteModal = false" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700 transition-colors">
                  Ta bort
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- End Modal -->
{% endif; %}
{% if($categories['pagination']['total'] > $categories['pagination']['per_page']) : %}
      <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
        <span class="block text-xs font-medium text-gray-600">{{ $categories['pagination']['total'] }} totalt</span>
        <span class="block text-xs font-medium text-gray-600">sida {{ $categories['pagination']['current_page'] }} av {{ calculate_total_pages($categories['pagination']['total'], $categories['pagination']['per_page']) }}</span>
      </div>
      <div class="mt-3">
        {{ paginate_links($categories['pagination'], 'admin.category.index', 2)|raw }}
      </div>
{% endif; %}
{% else : %}
      <p>Inga kategorier hittades.</p>
{% endif; %}
    </section>
{% endblock %}
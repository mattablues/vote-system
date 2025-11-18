{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Kategorier{% endblock %}
{% block pageId %}categories{% endblock %}
{% block body %}
    <section>
      <div class="flex items-start justify-between gap-4 mb-6">
        <h1 class="text-3xl font-semibold">Kategorier</h1>
        <div class="text-xs text-gray-600 font-medium hidden md:block mt-3">
          {{ $categories['pagination']['total'] ?? 0 }} totalt
        </div>
      </div>
{% if($categories['data']) : %}
      <table class="w-full">
        <thead>
          <tr class="text-left border-b border-t border-gray-200">
            <th data-cell="kategori" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Kategori</th>
            <th data-cell="beskrivning" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Beskrivning</th>
            <th data-cell="ämnen" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Ämnen</th>
            <th data-cell="röster totalt" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden lg:whitespace-nowrap">Röster totalt</th>
          </tr>
        </thead>
        <tbody>
{% foreach($categories['data'] as $category) : %}
          <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
            <td data-cell="kategori" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><a class="underline hover:no-underline delay-300" href="{{ route('votes.category.show', ['id' => $category->getAttribute('id')]) }}">{{ $category->getAttribute('category') }}</a></td>
            <td data-cell="beskrivning" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $category->getAttribute('description') }}</td>
            <td data-cell="ämnen" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize font-semibold">{{ $category->getAttribute('subject_count') }}</td>
            <td data-cell="röster" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize font-semibold">{{ $category->getAttribute('vote_count') }}</td>
          </tr>
{% endforeach; %}
        </tbody>
      </table>
{% if($categories['pagination']['total'] > $categories['pagination']['per_page']) : %}
      <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
        <span class="block text-xs font-medium text-gray-600">{{ $categories['pagination']['total'] }} totalt</span>
        <span class="block text-xs font-medium text-gray-600">sida {{ $categories['pagination']['current_page'] }} av {{ calculate_total_pages($categories['pagination']['total'], $categories['pagination']['per_page']) }}</span>
      </div>
      <div class="mt-3">
        {{ paginate_links($categories['pagination'], 'votes.category.index', 2)|raw }}
      </div>
{% endif; %}
{% else : %}
      <p>Inga kategorier hittades.</p>
{% endif; %}
    </section>
{% endblock %}
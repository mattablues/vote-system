{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Ämnen{% endblock %}
{% block pageId %}subjects{% endblock %}
{% block body %}
<section x-data="{ openVoteModal: null }">
  <div class="flex items-start justify-between gap-4 mb-6">
    <h1 class="text-3xl font-semibold">Ämnen</h1>
    <div class="text-xs text-gray-600 font-medium hidden md:block mt-3">
      {{ $subjects['pagination']['total'] ?? 0 }} totalt
    </div>
  </div>
{% if($subjects['data']) : %}
  <table class="w-full">
    <thead>
      <tr class="text-left border-b border-t border-gray-200">
        <th data-cell="ämne" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Ämne</th>
        <th data-cell="kategori" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Kategori</th>
        <th data-cell="röster totalt" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden lg:whitespace-nowrap">Röster totalt</th>
        <th data-cell="ja" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Ja</th>
        <th data-cell="vet inte" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden lg:whitespace-nowrap">Vet inte</th>
        <th data-cell="nej" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Nej</th>
        <th data-cell="åtgärd" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Åtgärd</th>
      </tr>
    </thead>
    <tbody>
{% foreach($subjects['data'] as $subject) : %}
      <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
        <td data-cell="ämne" class="w-full md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          {{ $subject->getAttribute('subject') }}
        </td>
        <td data-cell="kategori" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <a class="underline hover:no-underline delay-300" href="{{ route('votes.category.show', ['id' => $subject->getRelation('category')->getAttribute('id')]) }}">{{ $subject->getRelation('category')->getAttribute('category') }}</a>
        </td>
        <td data-cell="röster totalt" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <span class="font-semibold">{{ $subject->getAttribute('vote_count') }}</span>
        </td>
        <td data-cell="ja" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <span class="font-semibold">{{ $subject->getAttribute('vote_count_2') > 0 ? number_format($subject->getAttribute('vote_count_2') / $subject->getAttribute('vote_count') * 100, 2, '.', '') : 0 }}&percnt;</span>
        </td>
        <td data-cell="vet inte" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <span class="font-semibold">{{ $subject->getAttribute('vote_count_1') > 0 ? number_format($subject->getAttribute('vote_count_1') / $subject->getAttribute('vote_count') * 100, 2, '.', '') : 0 }}&percnt;</span>
        </td>
        <td data-cell="nej" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <span class="font-semibold">{{ $subject->getAttribute('vote_count_0') > 0 ? number_format($subject->getAttribute('vote_count_0') / $subject->getAttribute('vote_count') * 100, 2, '.', '') : 0 }}&percnt;</span>
        </td>
        <td data-cell="åtgärd" class="md:align-top px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
          <div class="flex items-center gap-1.5">
          {% if (isset($isVoterAuthenticated) && $isVoterAuthenticated) : %}
              {% if (isset($subjectIdsAlreadyVoted) && in_array((int)$subject->getAttribute('id'), $subjectIdsAlreadyVoted, true)) : %}
              <span class="inline-flex items-center text-xs font-semibold bg-gray-200/70 text-gray-400 py-1 px-2 rounded cursor-not-allowed">Rösta</span>
            {% else : %}
              <button type="button"
                  x-on:click="openVoteModal = {{ (int) $subject->getAttribute('id') }}"
                  class="inline-flex items-center text-xs font-semibold bg-blue-600 text-white py-1 px-2 rounded hover:bg-blue-700 active:bg-blue-800 transition-colors">
                Rösta
              </button>
            {% endif %}
          {% else : %}
            <a href="{{ route('votes.vote.index', ['id' => $subject->getAttribute('id')]) }}" class="inline-flex items-center text-xs font-semibold bg-blue-600 text-white py-1 px-2 rounded hover:bg-blue-700 transition-colors">Rösta</a>
          {% endif %}
          </div>
        </td>
      </tr>
{% endforeach; %}
    </tbody>
  </table>

  <div
    x-show="openVoteModal !== null"
    x-cloak
    x-on:keydown.escape.window="openVoteModal = null"
    role="dialog"
    aria-modal="true"
    class="fixed inset-0 z-50 overflow-y-auto"
  >
    <div x-show="openVoteModal !== null" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
    <div
      x-show="openVoteModal !== null" x-transition
      x-on:click="openVoteModal = null"
      class="relative flex min-h-screen items-center justify-center p-4"
    >
      <div
        x-on:click.stop
        class="relative w-full max-w-md rounded-2xl bg-white px-5 py-5 shadow-xl"
      >
        <h2 class="text-xl font-semibold text-gray-800">Rösta</h2>
        <p class="mt-1 text-sm text-gray-700">Välj ett alternativ och bekräfta.</p>

        <form
          x-data="{
            touched:false,
            valid() { return !!this.$el.querySelector('input[name=vote]:checked'); },
            showError() { this.touched = true; this.$refs.err.style.display = 'block'; },
            hideError() { this.$refs.err.style.display = 'none'; }
          }"
          x-bind:action="'{{ route('votes.vote.create', ['id' => 0]) }}'.replace('0', openVoteModal)"
          method="post"
          class="mt-4"
          x-on:submit.prevent="
            if (valid()) { hideError(); $el.submit(); }
            else { showError(); }
          "
        >
          {{ csrf_field()|raw }}
          <div class="relative mb-4 flex space-x-2">
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" name="vote" value="2" class="sr-only peer">
              <span class="block px-3 py-1.5 rounded-lg border border-gray-300 peer-checked:bg-green-500 peer-checked:text-white">Ja</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" name="vote" value="1" class="sr-only peer">
              <span class="block px-3 py-1.5 rounded-lg border border-gray-300 peer-checked:bg-yellow-500 peer-checked:text-white">Vet inte</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" name="vote" value="0" class="sr-only peer">
              <span class="block px-3 py-1.5 rounded-lg border border-gray-300 peer-checked:bg-red-500 peer-checked:text-white">Nej</span>
            </label>
            <span
              x-ref="err"
              style="display:none"
              class="block left-1 right-1 absolute top-12 text-xxs text-red-600 leading-3.5"
            >
              Du måste välja ett alternativ.
            </span>
          </div>

          <div class="mt-5 flex justify-end gap-2">
            <button type="button" x-on:click="openVoteModal = null" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 transition-colors">
              Avbryt
            </button>
            <button
              type="submit"
              x-on:click.stop
              class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700 transition-colors">
              Bekräfta röst
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

{% if($subjects['pagination']['total'] > $subjects['pagination']['per_page']) : %}
  <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
    <span class="block text-xs font-medium text-gray-600">{{ $subjects['pagination']['total'] }} totalt</span>
    <span class="block text-xs text-gray-600 font-medium">sida {{ $subjects['pagination']['current_page'] }} av {{ calculate_total_pages($subjects['pagination']['total'], $subjects['pagination']['per_page']) }}</span>
  </div>
  <div class="mt-3">
    {{ paginate_links($subjects['pagination'], 'votes.subject.index', 2)|raw }}
  </div>
{% endif; %}
{% else : %}
  <p>Inga kategorier hittades.</p>
{% endif; %}
</section>
{% endblock %}
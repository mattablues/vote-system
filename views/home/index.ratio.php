{% extends "layouts/sidebar.ratio.php" %}
{% block title %}{{ getenv('APP_NAME') ?: 'Hem' }}{% endblock %}
{% block pageId %}home{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-6">Gör din röst hörd</h1>
          <article class="mb-4">
            <h3 class="text-2xl mb-2">Lorem ipsum</h3>
            <p class="mb-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
            <h3 class=" text-[18px] font-semibold mb-1.5">Lorem ipsum</h3>
            <p class="mb-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
          </article>

          <article class="mb-4">
            <h3 class="text-2xl mb-2">Lorem ipsum</h3>
            <p class="mb-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
            <p class="mb-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
          </article>
        </div>

        <aside class="area-aside-right sticky-top">
          <div class="lg:mb-4 width-[250px] py-3 lg:py-0">
            <ul class="flex flex-col gap-3">
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Topp kategorier</h4>
                {% for ($i = 0; $i < count($categories); $i++) : %}
                <a href="{{ route('votes.category.show', ['id' => $categories[$i]->getAttribute('id')]) }}" class="text-sm underline hover:no-underline mb-1">{{ $categories[$i]->getAttribute('category') }} <span class="font-semibold">{{ $categories[$i]->getRelation('vote_count') > 0 ? prefix_message($categories[$i]->getRelation('vote_count'), 'röst', 'er') : '' }}</span></a>
                {% if ($i < count($categories) - 1) : %} &bullet; {% endif %}
                {% endfor; %}
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Senast tillagda kategorier</h4>
                {% for ($i = 0; $i < count($latestCategories); $i++) : %}
                  <a href="{{ route('votes.category.show', ['id' => $latestCategories[$i]->getAttribute('id')]) }}" class="text-sm underline hover:no-underline mb-1">
                    {{ $latestCategories[$i]->getAttribute('category') }}
                  </a>{% if ($i < count($latestCategories) - 1) : %} &bullet; {% endif %}
                {% endfor; %}
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Topp ämnen</h4>
                {% for ($i = 0; $i < count($subjects); $i++) : %}
                <a href="{{ route('votes.subject.index') }}" class="text-sm underline hover:no-underline mb-1">{{ $subjects[$i]->getAttribute('subject') }} <span class="font-semibold">{{ $subjects[$i]->getRelation('vote_count') > 0 ? prefix_message($subjects[$i]->getRelation('vote_count'), 'röst', 'er') : '' }}</span></a>
                {% if ($i < count($subjects) - 1) : %} &bullet; {% endif %}
                {% endfor; %}
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Senast tillagda ämnen</h4>
                {% for ($i = 0; $i < count($latestSubjects); $i++) : %}
                  <a href="{{ route('votes.subject.index') }}" class="text-sm underline hover:no-underline mb-1">
                    {{ $latestSubjects[$i]->getAttribute('subject') }}
                  </a>{% if ($i < count($latestSubjects) - 1) : %} &bullet; {% endif %}
                {% endfor; %}
              </li>
            </ul>
        </aside>
      </div>
    </section>
{% endblock %}
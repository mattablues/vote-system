{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Rösta{% endblock %}
{% block pageId %}vote{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-8">Rösta</h1>
{% if($subject) : %}
          <div class="w-full max-w-xl flex items-start gap-1 sm:gap-2 my-0.5">
            <span class="shrink-0 inline-block w-24 text-sm font-semibold text-gray-700">Kategori:</span>
            <span class="inline-block text-sm text-gray-700">{{ $subject->getRelation('category')->getAttribute('category') }}</span>
          </div>
          <div class="w-full max-w-xl flex items-start gap-1 sm:gap-2 my-0.5">
            <span class="shrink-0 inline-block w-24 text-sm font-semibold text-gray-700">Ämne:</span>
            <span class="inline-block text-sm text-gray-700">{{ $subject->getAttribute('subject') }}</span>
          </div>
          <div class="w-full max-w-xl flex items-start gap-1 sm:gap-2 mt-0.5 mb-2">
            <span class="shrink-0 inline-block w-24 text-sm font-semibold text-gray-700">Röster totalt:</span>
            <span class="inline-block text-sm text-gray-700">{{ $subject->getAttribute('vote_count') }}</span>
          </div>

          <hr class="mb-6 border-gray-200  max-w-xl"/>
          <p>Du måste ha ett <a href="{{ route('voter.create') }}" class="text-blue-600 underline hover:text-blue-800 hover:no-underline transition">registrerat</a> konto och vara <a href="{{ route('voter.auth.login') }}" class="text-blue-600 underline hover:text-blue-800 hover:no-underline transition">inloggad</a> för att kunna rösta</p>
{% else : %}
        <p>Ämnet kunde inte hittas.</p>
{% endif; %}
        </div>
        <aside class="area-aside-right sticky-top">
          <div class="lg:mb-4 width-[250px] py-3 lg:py-0">
            <ul class="flex flex-col gap-3">
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Aside</h4>
                <p class="text-sm pb-2">Lorem ipsum dolor sit amet...</p>
                <p class="text-sm">Lorem ipsum dolor sit amet...</p>
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
{% endblock %}
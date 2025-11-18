{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Nytt ämne{% endblock %}
{% block pageId %}create-subject{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-6">Skapa nytt ämne</h1>

          <form action="{{ route('votes.subject.store') }}" method="post" class="w-full bg-white max-w-xl">
            {{ csrf_field()|raw }}
            {{ honeypot_field()|raw }}

            <div class="relative mb-2">
              <label for="categories" class="block text-sm text-slate-600 mb-1.5 ml-1">Kategori</label>
              <select name="category_id" id="categories" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
                <option value="">Välj kategori</option>
                {% foreach ($categories as $category) : %}
                  <option value="{{ $category->id }}" {{ (string)$category->id === (string)old('category_id') ? 'selected' : '' }}>
                    {{ $category->category }}
                  </option>
                {% endforeach %}
              </select>
              {% if (error($errors, 'category_id')) : %}
              <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'category_id') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-1">
              <label for="subject" class="block text-sm text-slate-600 mb-1 ml-1">Ämne</label>
              <textarea name="subject" id="subject" cols="30" rows="6" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">{{ old('subject') }}</textarea>
              {% if (error($errors, 'subject')) : %}
              <span class="block right-1 absolute -bottom-2.5 text-xxs text-red-600">{{ error($errors, 'subject') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-8">
              <div class="flex gap-x-2 items-center">
                <button class="whitespace-nowrap text-sm py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
                <a href="{{ route('votes.subject.index') }}" class="whitespace-nowrap text-sm py-2 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
              </div>
              {% if (error($errors, 'form-error')) : %}
              <span class="block left-1 right-1 absolute top-12 text-xxs text-red-600 leading-3.5">{{ error($errors, 'form-error') }}</span>
              {% endif %}
            </div>
          </form>
        </div>

        <aside class="area-aside-right sticky-top">
          <div class="lg:mb-4 width-[250px] py-3 lg:py-0">
            <ul class="flex flex-col gap-3">
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="font-semibold mb-2">Kategorier</h4>
                {% foreach($categories as $category) : %}
                <a href="{{ route('votes.category.show', ['id' => $category->getAttribute('id')]) }}" class="block text-sm underline hover:no-underline mb-1">{{ $category->getAttribute('category') }} <span class="font-semibold whitespace-nowrap">{{ $category->getRelation('subject_count') > 0 ? prefix_message($category->getRelation('subject_count'), 'ämne', 'n') : '' }}</span></a>
                {% endforeach; %}
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
{% endblock %}
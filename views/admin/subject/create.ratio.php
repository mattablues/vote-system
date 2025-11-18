{% extends "layouts/admin.ratio.php" %}
{% block title %}Skapa nytt ämne{% endblock %}
{% block pageId %}create-subject{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl font-semibold mb-8">Skapa nytt ämne</h1>

      <form action="{{ route('admin.subject.store') }}" method="post" class="w-full bg-white max-w-2xl">
        {{ csrf_field()|raw }}

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

        <div class="relative">
          <label for="subject" class="block text-sm text-slate-600 mb-1 ml-1">Ämne</label>
          <textarea name="subject" id="subject" cols="30" rows="6" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">{{ old('subject') }}</textarea>
          {% if (error($errors, 'subject')) : %}
          <span class="block right-1 absolute -bottom-2.5 text-xxs text-red-600">{{ error($errors, 'subject') }}</span>
          {% endif %}
        </div>

        <div class="relative mt-5">
          <div class="flex gap-2 items-center">
            <button class="whitespace-nowrap text-sm py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
            <a href="{{ route('admin.subject.index') }}" class="whitespace-nowrap text-sm py-2 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
          </div>
          {% if (error($errors, 'form-error')) : %}
            <span class="block left-1 right-1 absolute top-12 text-xxs text-red-600 leading-3.5">{{ error($errors, 'form-error') }}</span>
          {% endif %}
        </div>
      </form>
    </section>
{% endblock %}
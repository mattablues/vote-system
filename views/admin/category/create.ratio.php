{% extends "layouts/admin.ratio.php" %}
{% block title %}Skapa kategori{% endblock %}
{% block pageId %}create-category{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl mb-8">Skapa ny kategori</h1>

      <form action="{{ route('admin.category.store') }}" method="post" class="w-full bg-white max-w-2xl">
         {{ csrf_field()|raw }}

        <div class="relative mb-2">
          <label for="category" class="block text-sm text-slate-600 mb-1.5 ml-1">Kategori</label>
          <input type="text" name="category" id="category" value="{{ old('category') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'category')) : %}
          <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'category') }}</span>
          {% endif %}
        </div>

        <div class="relative">
          <label for="description" class="block text-sm text-slate-600 ml-1">Beskrivning</label>
          <textarea name="description" id="description" cols="30" rows="10" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">{{ old('description') }}</textarea>
          {% if (error($errors, 'description')) : %}
          <span class="block right-1 absolute -bottom-2.5 text-xxs text-red-600">{{ error($errors, 'description') }}</span>
          {% endif %}
        </div>

        <div class="relative mt-5">
          <div class="flex gap-2 items-center">
            <button class="whitespace-nowrap text-sm py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
            <a href="{{ route('admin.category.index') }}" class="whitespace-nowrap text-sm py-2 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
          </div>
        </div>
      </form>
    </section>
{% endblock %}
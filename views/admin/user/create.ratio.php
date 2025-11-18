{% extends "layouts/admin.ratio.php" %}
{% block title %}Skapa nytt konto{% endblock %}
{% block pageId %}create-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
        <section>
          <h1 class="text-3xl font-semibold mb-8">Skapa nytt konto</h1>

          <form action="{{ route('admin.user.store') }}" method="post" enctype="multipart/form-data" class="w-full max-w-2xl">
             {{ csrf_field()|raw }}

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white/70 backdrop-blur-sm p-4 sm:p-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative">
                  <label for="firstname" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Förnamn</label>
                  <input
                    type="text"
                    name="first_name"
                    id="firstname"
                    value="{{ old('first_name') }}"
                    class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                  >
                  {% if (error($errors, 'first_name')) : %}
                  <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'first_name') }}</span>
                  {% endif %}
                </div>

                <div class="relative">
                  <label for="lastname" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Efternamn</label>
                  <input
                    type="text"
                    name="last_name"
                    id="lastname"
                    value="{{ old('last_name') }}"
                    class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                  >
                  {% if (error($errors, 'last_name')) : %}
                  <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'last_name') }}</span>
                  {% endif %}
                </div>

                <div class="relative sm:col-span-2">
                  <label for="email" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">E-postadress</label>
                  <input
                    type="text"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                  >
                  {% if (error($errors, 'email')) : %}
                  <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
                  {% endif %}
                </div>
              </div>

              <div class="mt-6 flex flex-wrap gap-2 items-center">
                <button class="inline-flex items-center text-sm py-2 px-3 border border-blue-600 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors cursor-pointer">
                  Spara
                </button>
                <a href="{{ route('admin.user.index') }}" class="inline-flex items-center text-sm py-2 px-3 bg-white text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                  Avbryt
                </a>
              </div>
            </div>
          </form>
        </section>
{% endblock %}
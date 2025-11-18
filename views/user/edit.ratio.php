{% extends "layouts/admin.ratio.php" %}
{% block title %}Redigera konto{% endblock %}
{% block pageId %}home{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
            <section>
              <h1 class="text-3xl font-semibold mb-8">Redigera konto</h1>

              <form action="{{ route('user.update') }}" method="post" enctype="multipart/form-data" class="w-full max-w-2xl">
                 {{ csrf_field()|raw }}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white/70 backdrop-blur-sm p-4 sm:p-6">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="relative">
                      <label for="firstname" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Förnamn</label>
                      <input
                        type="text"
                        name="first_name"
                        id="firstname"
                        value="{{ old('first_name') ?: $user->getAttribute('first_name') }}"
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
                        value="{{ old('last_name') ?: $user->getAttribute('last_name') }}"
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
                        value="{{ old('email') ?: $user->getAttribute('email') }}"
                        class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                      >
                      {% if (error($errors, 'email')) : %}
                      <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
                      {% endif %}
                    </div>

                    <!-- Återställd filuppladdning enligt tidigare lösning, med justerad höjd -->
                    <div class="relative sm:col-span-2">
                      <div class="form-upload-btn">
                        <label for="avatar" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Avatar</label>
                        <input
                          type="file"
                          name="avatar"
                          id="avatar"
                          accept="image/png, image/gif, image/jpeg, image/jpg"
                          class="w-full text-sm border border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                        >
                      </div>
                      {% if (error($errors, 'avatar')) : %}
                      <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'avatar') }}</span>
                      {% endif %}
                    </div>

                    <div class="relative">
                      <label for="password" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Lösenord</label>
                      <input
                        type="password"
                        name="password"
                        id="password"
                        class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                      >
                      {% if (error($errors, 'password')) : %}
                      <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'password') }}</span>
                      {% endif %}
                    </div>

                    <div class="relative">
                      <label for="password-confirmation" class="block text-xs uppercase tracking-wide text-gray-600 mb-1.5 ml-1">Repetera lösenord</label>
                      <input
                        type="password"
                        name="password_confirmation"
                        id="password-confirmation"
                        class="w-full text-sm border-gray-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 transition"
                      >
                      {% if (error($errors, 'password_confirmation')) : %}
                      <span class="block absolute right-1 -bottom-4 text-xxs text-red-600">{{ error($errors, 'password_confirmation') }}</span>
                      {% endif %}
                    </div>
                  </div>

                  <div class="mt-6 flex flex-wrap gap-2 items-center">
                    <button class="inline-flex items-center text-sm py-2 px-3 border border-blue-600 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors cursor-pointer">
                      Spara
                    </button>
                    <a href="{{ route('user.index') }}" class="inline-flex items-center text-sm py-2 px-3 bg-white text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                      Avbryt
                    </a>
                  </div>
                </div>
              </form>
            </section>
{% endblock %}
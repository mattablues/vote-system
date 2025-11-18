{% extends "layouts/auth.ratio.php" %}
{% block title %}Återställ lösenord{% endblock %}
{% block pageId %}password-reset{% endblock %}
{% block body %}
    <form action="{{ route('auth.password-reset.create', ['token' => $token]) }}" method="post" class="w-full bg-white mx-3 max-w-md py-6 px-6 rounded-lg shadow-md">
      <header class="flex justify-between items-center mb-3.5">
        <h1 class="text-2xl">Återställ lösenord</h1>
        <a href="{{ route('home.index') }}">
          <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10">
        </a>
      </header>
       {{ csrf_field()|raw }}
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="relative mb-2">
        <label for="password" class="block text-sm text-slate-600 mb-1.5 ml-1">Lösenord</label>
        <input type="password" name="password" id="password" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
        {% if (error($errors, 'password')) : %}
        <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'password') }}</span>
        {% endif %}
      </div>

      <div class="relative mb-2">
        <label for="password-confirmation" class="block text-sm text-slate-600 mb-1.5 ml-1">Repetera lösenord</label>
        <input type="password" name="password_confirmation" id="password-confirmation" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
        {% if (error($errors, 'password_confirmation')) : %}
        <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'password_confirmation') }}</span>
        {% endif %}
      </div>

      <div class="relative mt-6 mb-2">
      <button class="text-sm w-full whitespace-nowrap py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700 hover:border-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
        <div class="flex justify-end gap-2 items-center mt-1 mr-1.5">
          <a href="{{ route('auth.login.index') }}" class="text-sm text-blue-600 hover:text-blue-800 transition-all duration-300  text-left">Avbryt</a>
        </div>
        {% if (error($errors, 'form-error')) : %}
        <span class="block left-1 right-1 absolute top-16 text-xxs text-red-600 leading-3.5">{{ error($errors, 'form-error') }}</span>
        {% endif %}
      </div>
    </form>
{% endblock %}
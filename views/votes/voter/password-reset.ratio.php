{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Återställ lösenord{% endblock %}
{% block pageId %}password-reset{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-8">Återställ lösenord</h1>

          <form action="{{ route('voter.password-reset.create', ['token' => $token]) }}" method="post" class="w-full bg-white max-w-xl">
             {{ csrf_field()|raw }}
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="relative mb-2">
              <label for="password" class="block text-sm text-slate-600 mb-1.5 ml-1">Lösenord</label>
              <input type="password" name="password" id="password" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
              {% if (error($errors, 'password')) : %}
              <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'password') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-4">
              <label for="password-confirmation" class="block text-sm text-slate-600 mb-1.5 ml-1">Repetera lösenord</label>
              <input type="password" name="password_confirmation" id="password-confirmation" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
              {% if (error($errors, 'password_confirmation')) : %}
              <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'password_confirmation') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-8">
              <div class="flex gap-2 items-center">
                <button class="whitespace-nowrap text-sm py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
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
                <h4 class="font-semibold mb-2">Aside</h4>
                <p class="text-sm pb-2">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium dolorem laborum mollitia nobis qui. Consectetur doloremque iste natus nesciunt nisi.</p>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Commodi dolorem earum facilis impedit obcaecati rem repellendus rerum vel. Blanditiis commodi laudantium quam sed similique voluptate!</p>
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
{% endblock %}
{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Glömt lösenord{% endblock %}
{% block pageId %}password-forgot{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-8">Glömt lösenord</h1>

          <form action="{{ route('voter.password-forgot.create') }}" method="post" class="w-full bg-white max-w-xl">
            {{ csrf_field()|raw }}
            <div class="relative mb-4">
              <label for="email" class="block text-sm text-slate-600 mb-1.5 ml-1">E-postadress</label>
              <input type="text" name="email" id="email" value="{{ old('email') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
              {% if (error($errors, 'email')) : %}
              <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-8">
              <div class="flex gap-2 items-center">
                <button class="whitespace-nowrap text-sm py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Återställ</button>
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
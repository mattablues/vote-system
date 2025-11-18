{% extends "layouts/main.ratio.php" %}
{% block title %}Kontakta oss{% endblock %}
{% block pageId %}contact{% endblock %}
{% block body %}
    <section>
      <div class="container-centered layout-aside-both [--aside-left-w:250px] [--aside-right-w:250px]">
        <aside class="area-aside-left sticky-top pt-5 lg:py-5">
          <div class="lg:mb-4 width-[250px]">
            <ul class="flex flex-col gap-3">
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar left</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Beatae cumque distinctio exercitationem ipsa officiis porro quae quis recusandae.</p>
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar left</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Architecto facilis labore porro quam? Accusamus ad aliquam laudantium velit.</p>
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar left</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus itaque, laborum minus molestiae!</p>
              </li>
            </ul>
          </div>
        </aside>

        <div class="area-content">
          <h1 class="text-3xl font-semibold my-6">Kontakta oss</h1>

          <form action="{{ route('contact.create') }}" method="post" class="w-full bg-white max-w-xl">
            {{ csrf_field()|raw }}
            {{ honeypot_field()|raw }}

            <div class="flex gap-2">
              <div class="relative mb-2 w-full">
                <label for="first-name" class="block text-sm text-slate-600 mb-1.5 ml-1">Förnamn</label>
                <input type="text" name="first_name" id="first-name" value="{{ old('first_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
                {% if (error($errors, 'first_name')) : %}
                <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'first_name') }}</span>
                {% endif %}
              </div>

              <div class="relative mb-2 w-full">
                <label for="last-name" class="block text-sm text-slate-600 mb-1.5 ml-1">Efternamn</label>
                <input type="text" name="last_name" id="last-name" value="{{ old('last_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
                {% if (error($errors, 'last_name')) : %}
                <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'last_name') }}</span>
                {% endif %}
              </div>
            </div>

            <div class="relative mb-2">
              <label for="email" class="block text-sm text-slate-600 mb-1.5 ml-1">E-postadress</label>
              <input type="text" name="email" id="email" value="{{ old('email') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
              {% if (error($errors, 'email')) : %}
              <span class="block right-1 absolute -bottom-4 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-3">
              <label for="message" class="block text-sm text-slate-600 mb-1 ml-1">Meddelande</label>
              <textarea name="message" id="message" cols="30" rows="10" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">{{ old('message') }}</textarea>
              {% if (error($errors, 'message')) : %}
              <span class="block right-1 absolute -bottom-2.5 text-xxs text-red-600">{{ error($errors, 'message') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-8">
              <div class="flex gap-2 items-center">
                <button class="text-sm whitespace-nowrap py-2 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Skicka</button>
                {% if (error($errors, 'form-error')) : %}
                <span class="block left-1 right-1 absolute top-12 text-xxs text-red-600 leading-3.5">{{ error($errors, 'form-error') }}</span>
                {% endif %}
              </div>
            </div>
          </form>
        </div>

        <aside class="area-aside-right sticky-top pb-5 lg:py-5">
          <div class="lg:mb-4 width-[250px] py-3 lg:py-0">
            <ul class="flex flex-col gap-3">
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar right</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Beatae cumque distinctio exercitationem ipsa officiis porro quae quis recusandae.</p>
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar right</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Architecto facilis labore porro quam.</p>
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar right</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus itaque, laborum minus molestiae nostrum tempora vitae!</p>
              </li>
              <li class="border border-gray-200 px-3 pt-1 pb-2 rounded bg-white">
                <h4 class="mb-2 text-[18px]">Sidebar right</h4>
                <p class="text-sm">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Atque culpa dolores molestiae quas reiciendis?</p>
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
{% endblock %}
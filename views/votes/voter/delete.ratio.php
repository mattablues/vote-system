{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Avregistrering{% endblock %}
{% block pageId %}unregister{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-8">Avregistrering</h1>
          <p class="max-w-xl mb-3">Avregistrering raderar dig från att rösta och all din sparade information kommer att raderas. Du kan alltid registrera dig på nytt om du ångrar dig!</p>
          <p class="mb-8 max-w-xl font-semibold">Vill du fortsätta?</p>

          <form action="{{ route('voter.delete', ['token' => $token]) }}" method="post" class="w-full bg-white max-w-xl">
            {{ csrf_field()|raw }}

            <div class="relative mt-6 mb-8">
              <div class="flex gap-2 items-center">
                <a href="{{ route('home.index') }}" class="whitespace-nowrap text-sm py-2 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
                <button class="whitespace-nowrap text-sm py-2 px-3 border border-red-600 bg-red-600 hover:bg-red-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Ta bort</button>
              </div>
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
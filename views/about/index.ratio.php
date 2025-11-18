{% extends "layouts/sidebar.ratio.php" %}
{% block title %}Om oss{% endblock %}
{% block pageId %}about{% endblock %}
{% block pageClass %}about{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-right-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-6">Om oss</h1>

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
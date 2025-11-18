{% extends "layouts/admin.ratio.php" %}
{% block title %}Startsida{% endblock %}
{% block pageId %}dashboard{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <div class="layout-aside-right [--aside-w:250px]">
        <div class="area-content">
          <h1 class="text-3xl font-semibold mb-8">Startsida</h1>
          <h3 class="text-[20px] font-semibold mb-3">Övergripande</h3>

          <p class="mb-2">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci beatae hic magnam modi nemo officia recusandae, sed! Animi dolores illo maiores, officia placeat possimus quas quia quisquam reiciendis rerum voluptatem.</p>

          <p class="w-full md:max-w-[800px]">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci beatae hic magnam modi nemo officia recusandae, sed! Animi dolores illo maiores, officia placeat possimus quas quia quisquam reiciendis rerum voluptatem.</p>
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
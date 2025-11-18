{% extends "layouts/main.ratio.php" %}
{% block title %}Om oss{% endblock %}
{% block pageId %}about{% endblock %}
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
          <h1 class="text-3xl font-semibold my-6">Om oss</h1>
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
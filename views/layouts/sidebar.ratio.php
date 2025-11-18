{% extends "layouts/main.ratio.php" %}
{% block headerContainer %}
    <div class="container-base h-15 flex items-center justify-between">
    {% include "layouts/partials/header-inner.ratio.php" %}
    </div>
{% endblock %}

{% block footerContainer %}
    <div class="container-base py-4">
    {% include "layouts/partials/footer-inner.ratio.php" %}
    </div>
{% endblock %}

{% block content %}
  <div class="flex-1 min-h-[calc(100vh-108px)]">
    <div x-data="{ sidebarOpen:false }" class="relative">
      <!-- Sidebar: fixed på lg+, off-canvas på små -->
      <aside
        class="fixed lg:left-0 left-[-300px] top-[60px] h-[calc(100vh-60px)] w-[var(--sidebar-aside-w)] bg-white shadow z-40 overflow-y-auto hide-scrollbar transition-all duration-200"
        x-bind:class="sidebarOpen ? 'left-0' : 'left-[-300px]'"
        aria-label="Sidomeny">
        <div class="container-base py-4">
          <nav class="space-y-1 text-sm">
            <a href="{{ route('home.index') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Hem</a>
            <hr class="my-2 border-gray-200" />
            <a href="{{ route('votes.category.index') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Kategorier</a>
            <a href="{{ route('votes.subject.index') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Ämnen</a>
            <a href="{{ route('votes.subject.create') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Nytt ämne</a>
            <hr class="my-2 border-gray-200" />
            <a href="{{ route('voter.create') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Registrera</a>
            <a href="{{ route('voter.unregister') }}" class="block py-1.5 px-3 rounded hover:bg-gray-50 text-gray-700">Avregistrera</a>
          </nav>
        </div>
      </aside>

      <!-- Main: lämna plats för fixed sidebar på lg+ -->
      <main class="xl:max-w-[1220px] min-h-[calc(100vh-108px)]  transition-[margin] duration-200 lg:ml-[var(--sidebar-aside-w)]">
        <div class="container-base pt-4 pb-8">
          {% include "components/flash.ratio.php" %}
          {% include "components/noscript.ratio.php" %}
          {% yield body %}
        </div>
      </main>
    </div>
  </div>
{% endblock %}
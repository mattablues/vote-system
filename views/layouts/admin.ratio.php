<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta name="Authorization" content="Bearer {{ $currentToken }}">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
  <link rel="manifest" href="/icons/site.webmanifest">
</head>
<body x-data="{ openSidebar: false, openCloseModal: false, openDeleteModal: false, activeDropdown: null }" id="{% yield pageId %}" class="relative min-h-screen {% yield pageClass %}">
  <header class="sticky top-0 z-50 w-full bg-gray-900">
    <div class="container-base relative">
      <div class="h-15 flex items-center justify-between">
        <a href="{{ route('home.index') }}" class="flex items-center gap-2">
          <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10">
          <span class="text-xl text-white">{{ getenv('APP_NAME') }}</span>
        </a>

        <div class="flex items-center gap-2">
          <!-- Mobil: sökikon -->
          <button type="button" id="search-toggle" class="md:hidden p-2 rounded-md text-gray-200 hover:text-white hover:bg-gray-800" aria-label="Öppna sök">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197M15.803 15.803A7.5 7.5 0 1 1 5.196 5.196a7.5 7.5 0 0 1 10.607 10.607Z"/>
            </svg>
          </button>

          <!-- Namn + avatar (desktop/sm uppåt) -->
          <a href="{{ route('user.index') }}" class="hidden sm:flex items-center text-gray-200 hover:text-white gap-3 transition-colors duration-300">
            <span class="text-sm">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</span>
            <img src="{{ versioned_file($currentUser->getAttribute('avatar'), '/images/graphics/avatar.png') }}" alt="Avatar {{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}" class="w-9 h-9 rounded-full object-cover">
          </a>

          <!-- Hamburger -->
          <span class="text-white lg:hidden text-4xl cursor-pointer" x-on:click="openSidebar = !openSidebar" aria-label="Öppna meny">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
            </svg>
          </span>
        </div>
      </div>

      <!-- Sök: centrerad på md+, nedfällbar på mobil -->
      <div class="relative md:absolute md:left-1/2 md:-translate-x-1/2 md:top-1/2 md:-translate-y-1/2 z-[60]">
        <div id="search-wrap" class="hidden md:block md:static md:translate-x-0 md:translate-y-0 md:mt-0 mt-2">
          <div class="relative">
            <div class="p-2.5 px-4 flex items-center rounded-md bg-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-sm text-gray-300">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
              <label for="{% yield searchId %}" class="sr-only">Sök användare</label>
              <input class="text-[15px] ml-4 w-full md:w-[280px] bg-transparent border-none py-0 px-0 focus:ring-0 text-gray-100 placeholder:text-gray-300"
                     id="{% yield searchId %}" placeholder="Sök..." autocomplete="off">
            </div>

            <div id="search-dropdown"
                 class="absolute left-0 mt-1 w-full md:w-[420px] max-h-[60vh] overflow-auto bg-white text-gray-900 rounded-md shadow-lg border border-gray-200 hidden z-[70]">
              <div class="result-container"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="sidebar h-screen fixed top-0 lg:left-0 py-13 px-2 w-[var(--sidebar-w)] transition-all duration-200 overflow-y-auto text-center bg-gray-900 shadow hide-scrollbar z-40"
    :class="openSidebar ? 'left-[0]' : 'left-[var(--sidebar-w-neg)]'"
  >
    <div class="text-gray-100">
      <hr class="my-2 text-gray-600">
{% if($session->isAuthenticated()) : %}
      <div class="pt-2">
        <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>

          <span class="w-full text-left text-sm ml-1.5 text-gray-200">
            <a href="{{ route('dashboard.index') }}" class="w-full inline-block px-2.5 py-3" >Startsida</a>
          </span>
        </div>

        <div x-data="{ sidebarDropdown: false }">
          <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
              <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
            </svg>

            <div class="w-full flex justify-between items-center" x-on:click="sidebarDropdown = !sidebarDropdown">
              <span class="text-sm ml-4 py-3 text-gray-200">Konto</span>
              <span class="text-sm rotate-180" id="arrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"
                  :class="sidebarDropdown ? 'transition-all rotate-180 duration-300 ease-out' : 'transition-all rotate-0 duration-300 ease-out'"
                >
                  <path fill-rule="evenodd" d="M11.47 7.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 9.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
          </div>
          <ul
            x-cloak
            x-show="sidebarDropdown"
            x-transition:enter="transition-all duration-200 ease-out"
            x-transition:enter-start="-translate-y-5 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition-all duration-200 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-5 opacity-0"
            class="leading-7 text-left text-sm font-thin mt-2 w-4/5 mx-auto border-l-1 border-gray-700"
          >
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('user.index') }}" class="w-full inline-block py-2 px-8">Visa konto</a>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('user.edit') }}" class="w-full inline-block py-2 px-8">Redigera konto</a>
            </li>
{% if(!$currentUser->isAdmin()) : %}
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <button class="w-full text-left inline-block py-2 px-8 cursor-pointer" x-on:click="openCloseModal = true">Stäng konto</button>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <button class="w-full text-left inline-block py-2 px-8 cursor-pointer" x-on:click="openDeleteModal = true">Radera konto</button>
            </li>
{% endif; %}
          </ul>
        </div>
{% if($currentUser->hasAtLeast('moderator')) : %}
        <hr class="my-4 text-gray-400">
        <div x-data="{ sidebarDropdown: false }">
          <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
              <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.819l-.922 1.597a1.875 1.875 0 0 0 .432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 0 0 0 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 0 0-.432 2.385l.922 1.597a1.875 1.875 0 0 0 2.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 0 0 2.28-.819l.923-1.597a1.875 1.875 0 0 0-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 0 0 0-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 0 0-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 0 0-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 0 0-1.85-1.567h-1.843ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd" />
            </svg>

            <div class="w-full flex justify-between items-center" x-on:click="sidebarDropdown = !sidebarDropdown">
              <span class="text-sm ml-4 py-3 text-gray-200">Administration</span>
              <span class="text-sm rotate-180" id="arrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"
                :class="sidebarDropdown ? 'transition-all rotate-180 duration-300 ease-out' : 'transition-all rotate-0 duration-300 ease-out'"
                >
                  <path fill-rule="evenodd" d="M11.47 7.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 9.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
          </div>

          <ul
            x-cloak
            x-show="sidebarDropdown"
            x-transition:enter="transition-all duration-200 ease-out"
            x-transition:enter-start="-translate-y-5 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition-all duration-200 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-5 opacity-0"
            class="leading-7 text-left text-sm font-thin mt-2 w-4/5 mx-auto border-l-1 border-gray-700"
          >
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.user.index') }}" class="w-full inline-block py-2 px-8">Visa konton</a>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.user.closed') }}" class="w-full inline-block py-2 px-8">Stängda konton</a>
            </li>
{% if($currentUser->isAdmin()) : %}
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.user.create') }}" class="w-full inline-block py-2 px-8">Skapa nytt konton</a>
            </li>
{% endif; %}
          </ul>
        </div>

        <div x-data="{ sidebarDropdown: false }">
          <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
              <path fill-rule="evenodd" d="M12 6.75a5.25 5.25 0 0 1 6.775-5.025.75.75 0 0 1 .313 1.248l-3.32 3.319c.063.475.276.934.641 1.299.365.365.824.578 1.3.64l3.318-3.319a.75.75 0 0 1 1.248.313 5.25 5.25 0 0 1-5.472 6.756c-1.018-.086-1.87.1-2.309.634L7.344 21.3A3.298 3.298 0 1 1 2.7 16.657l8.684-7.151c.533-.44.72-1.291.634-2.309A5.342 5.342 0 0 1 12 6.75ZM4.117 19.125a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75v-.008Z" clip-rule="evenodd" />
              <path d="m10.076 8.64-2.201-2.2V4.874a.75.75 0 0 0-.364-.643l-3.75-2.25a.75.75 0 0 0-.916.113l-.75.75a.75.75 0 0 0-.113.916l2.25 3.75a.75.75 0 0 0 .643.364h1.564l2.062 2.062 1.575-1.297Z" />
              <path fill-rule="evenodd" d="m12.556 17.329 4.183 4.182a3.375 3.375 0 0 0 4.773-4.773l-3.306-3.305a6.803 6.803 0 0 1-1.53.043c-.394-.034-.682-.006-.867.042a.589.589 0 0 0-.167.063l-3.086 3.748Zm3.414-1.36a.75.75 0 0 1 1.06 0l1.875 1.876a.75.75 0 1 1-1.06 1.06L15.97 17.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>

            <div class="w-full flex justify-between items-center" x-on:click="sidebarDropdown = !sidebarDropdown">
              <span class="text-sm ml-4 py-3 text-gray-200">System</span>
              <span class="text-sm rotate-180" id="arrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"
                :class="sidebarDropdown ? 'transition-all rotate-180 duration-300 ease-out' : 'transition-all rotate-0 duration-300 ease-out'"
                >
                  <path fill-rule="evenodd" d="M11.47 7.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 9.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
          </div>

          <ul
            x-cloak
            x-show="sidebarDropdown"
            x-transition:enter="transition-all duration-200 ease-out"
            x-transition:enter-start="-translate-y-5 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition-all duration-200 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-5 opacity-0"
            class="leading-7 text-left text-sm font-thin mt-2 w-4/5 mx-auto border-l-1 border-gray-700"
          >
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.health.index') }}" class="w-full inline-block py-2 px-8">Status</a>
            </li>
          </ul>
        </div>
{% endif; %}
{% if($currentUser->hasAtLeast('editor')) : %}
        <div x-data="{ sidebarDropdown: false }">
          <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
              <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
              <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.163 3.75A.75.75 0 0 1 10 12h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
            </svg>

            <div class="w-full flex justify-between items-center" x-on:click="sidebarDropdown = !sidebarDropdown">
              <span class="text-sm ml-4 py-3 text-gray-200">Röstning</span>
              <span class="text-sm rotate-180" id="arrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"
                :class="sidebarDropdown ? 'transition-all rotate-180 duration-300 ease-out' : 'transition-all rotate-0 duration-300 ease-out'"
                >
                  <path fill-rule="evenodd" d="M11.47 7.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 9.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
          </div>

          <ul
            x-cloak
            x-show="sidebarDropdown"
            x-transition:enter="transition-all duration-200 ease-out"
            x-transition:enter-start="-translate-y-5 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition-all duration-200 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-5 opacity-0"
            class="leading-7 text-left text-sm font-thin mt-2 w-4/5 mx-auto border-l-1 border-gray-700"
          >
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.category.index') }}" class="w-full inline-block py-2 px-8">Kategorier</a>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.subject.index') }}" class="w-full inline-block py-2 px-8">Ämnen</a>
            </li>
{% if($currentUser->hasAtLeast('moderator')) : %}
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.voter.index') }}" class="w-full inline-block py-2 px-8">Röstberättigade</a>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.category.create') }}" class="w-full inline-block py-2 px-8">Skapa ny kategori</a>
            </li>
            <li class="mt-1 ml-1 rounded-md cursor-pointer hover:bg-gray-700">
              <a href="{{ route('admin.subject.create') }}" class="w-full inline-block py-2 px-8">Skapa nytt ämne</a>
            </li>
{% endif; %}
          </ul>
        </div>
{% endif; %}
        <div>
          <div class="mt-2 px-4 flex items-center rounded-md transition-all duration-300 cursor-pointer hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
              <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>

            <form action="{{ route('auth.logout.index') }}" method="post" class="w-full text-sm ml-4 text-gray-200">
              {{ csrf_field()|raw }}
              <button class="w-full py-3 text-left cursor-pointer">Logout</button>
            </form>
          </div>
        </div>
      </div>
{% endif; %}
    </div>
  </div>
    {% include "components/modal-close.ratio.php" %}
    {% include "components/modal-delete.ratio.php" %}
  <main
    class="xl:max-w-[1140px] lg:ml-[var(--sidebar-w)] pt-4 px-3 md-px-5 lg:px-7 pb-2 min-h-[calc(100vh-108px)]"
    x-on:click="if (openSidebar) openSidebar = false"
  >
    {% include "components/flash-box.ratio.php" %}
    {% include "components/noscript.ratio.php" %}
    {% yield body %}
  </main>

  <footer class="text-center">
    <div class="container-base py-4">
      <p class="text-xs text-slate-500  font-semibold text-center">
        &copy;{{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }}
        | <a href="{{ route('cookie.index') }}" class="underline hover:no-underline transition duration-300">Cookies</a>
      </p>
    </div>
  </footer>
  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script src="{{ versioned_file('/js/app.js') }}"></script>
  {% yield script %}
</body>
</html>
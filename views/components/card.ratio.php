    <div class="flex flex-col text-white w-64 min-h-80 p-3 rounded-lg shadow-3xl {{ $class ?? '' }}">
    {% if(isset($header)) : %}
      <header class="{{ $headerClass ?? '' }}">
        <h3 class="text-2xl text-center mb-3">{{ $header }}</h3>
      </header>
    {% endif; %}
      <div class="flex-1 px-2 {{ $bodyClass ?? '' }}">
        <p class="">{{ $slot }}</p>
      </div>
    {% if(isset($footer)) : %}
      <footer class="flex justify-between items-center px-2 {{ $footerClass ?? '' }}">
        {{ $footer }}
        <button class="px-3 mt-auto text-sm bg-white text-blue-800 rounded cursor-pointer {{ $buttonClass ?? '' }}">Läs mera...</button>
      </footer>
    {% endif; %}
    </div>
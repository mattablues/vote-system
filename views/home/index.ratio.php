{% extends "layouts/main.ratio.php" %}
{% block title %}{{ getenv('APP_NAME') ?: 'Hem' }}{% endblock %}
{% block pageId %}home{% endblock %}
{% block body %}
    <section class="py-6 bg-gray-100 h-[calc(100vh-60px)]">
      <div class="flex justify-center items-center h-full">
        <div>
          <h1 class="text-4xl md:text-6xl lg:text-8xl font-extralight text-slate-400 w-au">Hero Section</h1>
        </div>
      </div>
    </section>

    <section class="py-16">
      <div class="container-centered">
        <div class="flex justify-center content-stretch gap-5 flex-wrap">
          <x-card class="bg-blue-600">
            <x-slot:header>Card</x-slot:header>
              Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy
              <x-slot:button>Min nya knapp</x-slot:button>
            <x-slot:footer></x-slot:footer>
          </x-card>

          <x-card class="bg-red-600">
            <x-slot:header>Card</x-slot:header>
              The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from "de Finibus Bonorum et Malorum" by Cicero are also reproduced in their exact original form.
              <x-slot:button>Min nya knapp</x-slot:button>
            <x-slot:footer></x-slot:footer>
          </x-card>

          <x-card class="bg-green-600">
            <x-slot:header>Card</x-slot:header>
              There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage.
              <x-slot:button>Min nya knapp</x-slot:button>
            <x-slot:footer></x-slot:footer>
          </x-card>
        </div>
      </div>
    </section>
{% endblock %}
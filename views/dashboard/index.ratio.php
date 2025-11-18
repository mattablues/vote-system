{% extends "layouts/admin.ratio.php" %}
{% block title %}Startsida{% endblock %}
{% block pageId %}dashboard{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl mb-8">Startsida</h1>
      <h3 class="text-[20px] font-semibold mb-3">Översikt röstsystem</h3>

      <div class="flex justify-center lg:justify-start gap-5 flex-wrap">
        <a href="{{ route('admin.category.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Kategorier</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numCategories }}</span>
          </div>
        </a>
        <a href="{{ route('admin.subject.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded  hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Publicerade ämnen</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numSubjectsPublished }}</span>
          </div>
        </a>
        <a href="{{ route('admin.subject.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded  hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Opublicerade ämnen</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numSubjectsUnpublished }}</span>
          </div>
        </a>
        <a href="{{ route('admin.voter.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded  hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Aktiverade röstberättigade</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numActivatedVoters }}</span>
          </div>
        </a>
        <a href="{{ route('admin.voter.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded  hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Ej aktiverade röstberättigade</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numUnactivatedVoters }}</span>
          </div>
        </a>
        <a href="{{ route('admin.voter.index') }}" class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded  hover:bg-gray-100 transition-colors duration-300">
          <div class="text-center">
            <span class="block text-sm mb-2">Blockerade röstberättigade</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numBlockedVoters }}</span>
          </div>
        </a>
        <div class="flex items-center justify-center w-full md:w-52 h-36 border border-gray-200 rounded">
          <div class="text-center">
            <span class="block text-sm mb-2">Totalt antal röster</span>
            <span class="block text-5xl font-light text-gray-900">{{ $numVotes }}</span>
          </div>
        </div>
      </div>
    </section>
{% endblock %}
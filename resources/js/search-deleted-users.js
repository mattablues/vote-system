import Search from './search';

export default class SearchDeletedUsers extends Search {
    constructor(searchInputId, mainContentSelector, token) {
        super(searchInputId, mainContentSelector, token);
        this.meta = { term: '', total: 0, per_page: 10, current_page: 1, last_page: 0 };
    }

    async performSearch(term, page = 1) {
        try {
            this.showLoading();

            const response = await fetch('/api/v1/search/deleted-users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    search: {
                        term: term,
                        current_page: page,
                        per_page: this.meta?.per_page ?? 10
                    }
                })
            });

            if (!response.ok) {
                throw new Error('Något gick fel med sökningen.');
            }

            const responseJson = await response.json();
            this.results = responseJson.body.data || [];
            this.meta = responseJson.body.meta || this.meta;
            this.renderResults();

        } catch (error) {
            console.error('Sökfel:', error.message);
            if (this.resultContainer) {
                this.resultContainer.innerHTML = `<p class="p-3 text-red-500">Kunde inte hämta resultaten. Försök igen.</p>`;
            } else if (this.mainContent) {
                this.mainContent.innerHTML = `<p class="text-red-500">Kunde inte hämta resultaten. Försök igen.</p>`;
            }
        } finally {
            this.hideLoading();
        }
    }

    renderResults() {
        if (this.resultContainer) {
            this.resultContainer.innerHTML = '';

            if (this.results.length > 0) {
                const ul = document.createElement('ul');
                ul.className = 'divide-y divide-gray-100';

                this.results.forEach(result => {
                    const li = document.createElement('li');
                    li.className = 'px-3 py-1.5 hover:bg-gray-50';
                    const userRoute = `/user/${result.id}/show`;

                    li.innerHTML = `
                        <a href="${userRoute}" class="flex items-center gap-3">
                            <img src="${result.avatar_url || result.avatar}" alt="${result.first_name}" class="w-8 h-8 rounded-full object-cover">
                            <div>
                                <div class="text-sm font-medium text-blue-600 hover:underline">${result.first_name} ${result.last_name}</div>
                                <p class="text-xs text-gray-600">${result.email}</p>
                            </div>
                        </a>
                    `;
                    ul.appendChild(li);
                });

                this.resultContainer.appendChild(ul);

                const pager = this.renderPager();
                if (pager) this.resultContainer.appendChild(pager);

            } else {
                this.resultContainer.innerHTML = `<p class="p-3 text-gray-500">Inga resultat hittades.</p>`;
            }

            this.showDropdown();
            return;
        }

        // Fallback till mainContent
        if (this.mainContent) {
            let resultContainer = this.mainContent.querySelector('.result-container');
            if (!resultContainer) {
                resultContainer = document.createElement('div');
                resultContainer.classList.add('result-container');
                this.mainContent.appendChild(resultContainer);
            }

            resultContainer.innerHTML = '';

            if (this.results.length > 0) {
                const ul = document.createElement('ul');
                ul.classList.add('search-results');

                this.results.forEach(result => {
                    const li = document.createElement('li');
                    li.classList.add('search-result-item');

                    const userRoute = `/user/${result.id}/show`;

                    li.innerHTML = `
                        <div class="flex items-center gap-4">
                            <img src="${result.avatar}" alt="${result.first_name}" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <a href="${userRoute}" class="font-semibold text-blue-600 hover:underline">${result.first_name} ${result.last_name}</a>
                                <p class="text-sm text-gray-600">${result.email}</p>
                            </div>
                        </div>
                    `;
                    ul.appendChild(li);
                });

                resultContainer.appendChild(ul);

                const pager = this.renderPager();
                if (pager) resultContainer.appendChild(pager);

            } else {
                resultContainer.innerHTML = `<p class="text-gray-500">Inga resultat hittades.</p>`;
            }
        }
    }
}
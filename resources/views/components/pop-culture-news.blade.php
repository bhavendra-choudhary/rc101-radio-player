@props([
    'news' => collect()
])

@php
    $defaultArtUrl = asset(\App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));
    $fmt = \App\Models\SiteSetting::getFormatConfig();
@endphp

<div class="pop-culture-news-section" id="popCultureNewsSection">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-4">
                    <h3 class="display-5">
                        <i class="fas fa-newspaper me-2 text-warning"></i>
                        {{ \App\Models\SiteSetting::get('front_featured_title', $fmt['news_section_title']) }}
                    </h3>
                    <p class="lead text-muted mt-2">
                        Stay updated with the latest {{ strtolower($fmt['label']) }} music news, artist updates, and stories.
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="newsLoading" class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading latest news...</p>
        </div>

        <!-- Error State -->
        <div id="newsError" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="newsErrorMessage">Failed to load news. Please try again.</span>
            <button class="btn btn-outline-danger btn-sm ms-2" onclick="refreshPopCultureNews()">
                <i class="fas fa-redo me-1"></i>Retry
            </button>
        </div>

        <!-- News Grid -->
        <div id="newsGrid" class="row g-4 d-none">
            <!-- News articles will be dynamically inserted here -->
        </div>
        <!-- Database News (Hidden, appended after API news) -->
        <div id="dbNewsGrid" class="row g-4 d-none">
            @foreach ($news as $item)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ $item->source_url ?? '#' }}" target="_blank" class="news-card-link">
                        <div class="news-card">
                            <div class="news-card-img-wrapper {{ !$item->image_path ? 'has-fallback-logo' : '' }}">
                                <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : $defaultArtUrl }}"
                                    class="news-card-image {{ !$item->image_path ? 'is-fallback-logo' : '' }}"
                                    alt="{{ $item->title }}"
                                    onerror="this.onerror=null;this.src='{{ $defaultArtUrl }}';this.classList.add('is-fallback-logo');this.parentElement.classList.add('has-fallback-logo');">
                            </div>
                            <div class="news-card-body">
                                <h5 class="news-card-title">{{ $item->title }}</h5>
                                <div class="news-card-description-wrapper">
                                    <p class="news-card-description">
                                        {{ \Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($item->content), ENT_QUOTES, 'UTF-8'))), 200) }}
                                    </p>
                                </div>
                                <div class="news-card-meta">
                                    <span class="news-learn-more-btn">
                                        Learn More <i class="fas fa-arrow-right"></i>
                                    </span>
                                    <span class="news-card-date">
                                        <i class="far fa-calendar-alt"></i> {{ $item->published_at ? $item->published_at->format('M d, Y') : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Empty State -->
        <div id="newsEmpty" class="text-center py-5 d-none">
            <i class="fas fa-newspaper text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3 text-muted">No news available</h4>
            <p class="text-muted">Check back later for the latest updates.</p>
        </div>
    </div>
</div>

{{-- Pop culture news styles are centralized in public/css/custom.css --}}

<script>
function fetchPopCultureNews() {
    const loadingEl = document.getElementById('newsLoading');
    const errorEl = document.getElementById('newsError');
    const gridEl = document.getElementById('newsGrid');
    const emptyEl = document.getElementById('newsEmpty');
    
    // Show loading state
    loadingEl.classList.remove('d-none');
    errorEl.classList.add('d-none');
    gridEl.classList.add('d-none');
    emptyEl.classList.add('d-none');
    
    fetch('{{ url("api/news/pop-culture") }}')
        .then(response => response.json())
        .then(data => {
            loadingEl.classList.add('d-none');
            
            if (data.success && data.data && data.data.length > 0) {
                displayNewsArticles(data.data);
                gridEl.classList.remove('d-none');
            } else {
                loadingEl.classList.add('d-none');

                // Show DB news if API is empty
                const dbGrid = document.getElementById('dbNewsGrid');
                if (dbGrid && dbGrid.children.length > 0) {
                    displayNewsArticles([]);
                    gridEl.classList.remove('d-none');
                } else {
                    emptyEl.classList.remove('d-none');
                }
            }

        })
        .catch(error => {
            console.error('Error fetching pop culture news:', error);
            loadingEl.classList.add('d-none');
            // Fallback to DB news if API fails
            const dbGrid = document.getElementById('dbNewsGrid');
            if (dbGrid && dbGrid.children.length > 0) {
                displayNewsArticles([]);
                gridEl.classList.remove('d-none');
            } else {
                errorEl.classList.remove('d-none');
            }

            document.getElementById('newsErrorMessage').textContent = 
                'Failed to load news. Please check your connection and try again.';
        })
.finally(() => {
    if (typeof window.notifyAsyncLoadComplete === 'function') {
        window.notifyAsyncLoadComplete();
    }
});
}

function extractClientArticleEntities(title, desc) {
    const text = ((title || '') + ' ' + (desc || '')).toLowerCase();
    const entities = [];
    const knownArtists = [
        'dolly parton', 'dolly', 'morgan wallen', 'luke combs', 'zach bryan', 'jelly roll',
        'lainey wilson', 'chris stapleton', 'george strait', 'cody johnson', 'carrie underwood',
        'tim mcgraw', 'kenny chesney', 'kacey musgraves', 'miranda lambert', 'jason aldean',
        'blake shelton', 'willie nelson', 'eric church', 'thomas rhett', 'parker mccollum',
        'kane brown', 'tyler childers', 'colter wall', 'sturgill simpson', 'post malone',
        'megan moroney', 'warren zeiders', 'turnpike troubadours', 'shaboozey', 'charley crockett',
        'ella langley', 'zach top', 'treaty oak revival', 'sam barber', 'chayce beckham',
        'riley green', 'dasha', 'nate smith', 'tucker wetmore', 'dierks bentley', 'keith urban',
        'alan jackson', 'reba mcentire', 'reba', 'brooks & dunn', 'jon pardi', 'dustin lynch',
        'midland', 'whiskey myers', 'hardy', 'ernest', 'carly pearce', 'kelsea ballerini',
        'maren morris', 'sam hunt', 'jordan davis', 'walker hayes', 'russell dickerson',
        'brett young', 'chris young', 'lee brice', 'justin moore', 'josh turner', 'randy travis',
        'garth brooks', 'vince gill', 'toby keith', 'dwight yoakam', 'martina mcbride', 'shania twain',
        'faith hill', 'trisha yearwood', 'alabama', 'the judds', 'beyonce', 'taylor swift', 'miley cyrus'
    ];
    for (let i = 0; i < knownArtists.length; i++) {
        const a = knownArtists[i];
        const escaped = a.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp('\\b' + escaped + '\\b', 'i');
        if (regex.test(text)) {
            entities.push(a === 'dolly' ? 'dolly parton' : (a === 'reba' ? 'reba mcentire' : a));
        }
    }
    const properMatches = (title || '').match(/\b([A-Z][a-z]{2,}(?:\s+[A-Z][a-z]{2,})+)\b/g);
    if (properMatches) {
        const genericPhrases = ['country music', 'music scene', 'united states', 'press release', 'breaking news', 'album review', 'grand ole', 'music video', 'hall fame'];
        properMatches.forEach(p => {
            const lp = p.toLowerCase();
            if (!genericPhrases.includes(lp) && !entities.includes(lp)) {
                entities.push(lp);
            }
        });
    }
    return entities;
}

let allNews = [];
let visibleNewsCount = 8;

function displayNewsArticles(articles) {
    const dbGrid = document.getElementById('dbNewsGrid');
    const rawCandidates = [];

    // 1️⃣ Add DATABASE news first (admin news)
    if (dbGrid && dbGrid.children.length > 0) {
        Array.from(dbGrid.children).forEach(dbChild => {
            const titleEl = dbChild.querySelector('.news-card-title');
            const descEl = dbChild.querySelector('.news-card-description');
            rawCandidates.push({
                type: 'dom',
                element: dbChild.cloneNode(true),
                title: titleEl ? titleEl.textContent.trim() : '',
                description: descEl ? descEl.textContent.trim() : ''
            });
        });
    }

    // 2️⃣ Append API news AFTER database news
    if (articles && articles.length > 0) {
        articles.forEach(article => {
            rawCandidates.push({
                type: 'api',
                data: article,
                title: article.title || '',
                description: article.description || article.content || ''
            });
        });
    }

    // 3️⃣ Enforce strict generic diversity and deduplication across both DB & API articles
    allNews = [];
    const seenTitles = new Set();
    const entityCounts = {};
    const deferredList = [];

    rawCandidates.forEach(item => {
        if (!item.title) return;
        const normTitle = item.title.toLowerCase().replace(/[^a-z0-9]/g, '');
        if (seenTitles.has(normTitle)) {
            return; // Skip duplicate title
        }
        seenTitles.add(normTitle);

        const entities = extractClientArticleEntities(item.title, item.description);
        let canAdd = true;
        for (let i = 0; i < entities.length; i++) {
            if ((entityCounts[entities[i]] || 0) >= 1) {
                canAdd = false;
                break;
            }
        }

        if (canAdd) {
            allNews.push(item);
            entities.forEach(ent => {
                entityCounts[ent] = (entityCounts[ent] || 0) + 1;
            });
        } else {
            deferredList.push(item);
        }
    });

    // If needed to fill initial view, append deferred items at the end
    if (allNews.length < 8 && deferredList.length > 0) {
        deferredList.forEach(item => {
            if (allNews.length < 12) {
                allNews.push(item);
            }
        });
    }

    visibleNewsCount = 8;
    renderNewsBatch();
}

function renderNewsBatch() {
    const gridEl = document.getElementById('newsGrid');
    gridEl.innerHTML = '';
    
    const newsToShow = allNews.slice(0, visibleNewsCount);
    
    newsToShow.forEach(item => {
        if (item.type === 'dom') {
            gridEl.appendChild(item.element.cloneNode(true));
        } else {
            const newsCard = createNewsCard(item.data);
            gridEl.appendChild(newsCard);
        }
    });

    // Make sure all database news cards are visible and have same structure
    const allNewsCards = gridEl.querySelectorAll('.news-card');
    allNewsCards.forEach(card => {
        card.classList.add('news-card');
    });
    
    // Check load more button
    let loadMoreContainer = document.getElementById('news-load-more-container');
    if (!loadMoreContainer) {
        loadMoreContainer = document.createElement('div');
        loadMoreContainer.id = 'news-load-more-container';
        loadMoreContainer.className = 'news-load-more-wrapper my-4';
        loadMoreContainer.innerHTML = `
            <span class="load-more-divider-line"></span>
            <button id="load-more-news-btn" class="news-load-more-btn" onclick="loadMoreNews()">
                <i class="fas fa-newspaper me-2 text-white"></i> Load More News
            </button>
            <span class="load-more-divider-line"></span>
        `;
        gridEl.insertAdjacentElement('afterend', loadMoreContainer);
    }
    
    if (allNews.length > visibleNewsCount) {
        loadMoreContainer.style.setProperty('display', 'flex', 'important');
        loadMoreContainer.classList.remove('d-none');
    } else {
        loadMoreContainer.style.setProperty('display', 'none', 'important');
        loadMoreContainer.classList.add('d-none');
    }

    // Apply equal height to cards in each row
    setTimeout(() => {
        equalizeCardHeights();
    }, 100);
}

function loadMoreNews() {
    visibleNewsCount += 8;
    renderNewsBatch();
}
function createNewsCard(article) {
    const col = document.createElement('div');
    col.className = 'col-lg-4 col-md-6';

    const defaultArt = '{{ $defaultArtUrl }}';
    // Use defaultImage from API response if available, otherwise fall back to local default
    const fallbackImg = article.defaultImage || defaultArt;
    const imageUrl    = article.urlToImage || article.image || fallbackImg;
    const isFallback  = (!article.urlToImage && !article.image) ||
                        imageUrl === fallbackImg ||
                        imageUrl === defaultArt ||
                        (typeof imageUrl === 'string' && (imageUrl.indexOf('alexaicon') !== -1 || imageUrl.indexOf('rc101') !== -1 || imageUrl.indexOf('default') !== -1));

    const formattedDate = article.formattedDate || (article.publishedAt ? new Date(article.publishedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '');

    col.innerHTML = `
        <a href="${article.url || '#'}" target="_blank" class="news-card-link">
            <div class="news-card">
                <div class="news-card-img-wrapper ${isFallback ? 'has-fallback-logo' : ''}" style="width:100%;height:200px;overflow:hidden;background:${isFallback ? '#ffffff' : 'linear-gradient(135deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%)'};background-size:200% 100%;${isFallback ? '' : 'animation:news-shimmer 1.5s infinite;'}">
                    <img src="${imageUrl}"
                         alt="${(article.title || '').replace(/"/g, '&quot;')}"
                         class="news-card-image ${isFallback ? 'is-fallback-logo' : ''}"
                         style="opacity:0;transition:opacity 0.3s ease;"
                         onload="this.style.opacity='1';this.parentElement.style.animation='none';if(this.classList.contains('is-fallback-logo')){this.parentElement.style.background='#ffffff';}else{this.parentElement.style.background='transparent';}"
                         onerror="this.onerror=null;this.src='${fallbackImg}';this.classList.add('is-fallback-logo');this.parentElement.classList.add('has-fallback-logo');this.style.opacity='1';this.parentElement.style.animation='none';this.parentElement.style.background='#ffffff';">
                </div>
                <div class="news-card-body">
                    <h5 class="news-card-title">${article.title || 'News Article'}</h5>
                    <div class="news-card-description-wrapper">
                        <p class="news-card-description">${article.description || 'No description available'}</p>
                    </div>
                    <div class="news-card-meta">
                        <span class="news-learn-more-btn">
                            Learn More <i class="fas fa-arrow-right"></i>
                        </span>
                        <span class="news-card-date">
                            ${formattedDate ? `<i class="far fa-calendar-alt"></i> ${formattedDate}` : ''}
                        </span>
                    </div>
                </div>
            </div>
        </a>
    `;

    return col;
}


function equalizeCardHeights() {
    const rows = document.querySelectorAll('#newsGrid .row');
    if (!rows.length) return;
    
    // For each row group (on larger screens, 3 per row, on medium 2, on small 1)
    const cards = document.querySelectorAll('#newsGrid .news-card');
    
    // Group cards by their row position
    let maxHeight = 0;
    
    // First, reset all heights to auto
    cards.forEach(card => {
        card.style.height = 'auto';
    });
    
    // Then set the maximum height for all cards
    cards.forEach(card => {
        const height = card.offsetHeight;
        if (height > maxHeight) {
            maxHeight = height;
        }
    });
    
    // Apply the maximum height to all cards
    cards.forEach(card => {
        card.style.height = maxHeight + 'px';
    });
}

function refreshPopCultureNews() {
    fetchPopCultureNews();
}

// Initialize news fetching when page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchPopCultureNews();
    window.addEventListener('resize', equalizeCardHeights);
});
</script>
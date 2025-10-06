@php
    $trustpilotReviewContent = getContent('trustpilot_review.content', true);
@endphp

@if ($trustpilotReviewContent)
    @push('style')
        <!-- Swiper CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css"/>

        <!-- Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">

        <style>
            .iRKbXZ {
                max-width: 100% !important;
            }
            .ejKmWB .review-text p {
                font-family: "Roboto", sans-serif;
            }

            /* review styale code start */
            .review-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
            }
            .review-header h3 {
                margin: 0;
                font-size: 1.5rem;
            }
            .rating-summary {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 1rem;
            }
            .stars {
                color: #fbbf24;
            }
            .review-count {
                color: gray;
                font-size: 0.9rem;
            }
            .leave-review-btn {
                background-color: #6366f1;
                color: white;
                border: none;
                padding: 10px 18px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 0.9rem;
            }
            .review-slide {
                background-color: white;
                padding: 16px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                margin: 10px;
                font-family: 'Inter', sans-serif;
            }
            .review-header {
                display: flex;
                gap: 12px;
                align-items: flex-start;
                margin-bottom: 10px;
            }
            .avatar {
                width: 40px;
                height: 40px;
                background-color: #ccc;
                border-radius: 50%;
                font-weight: bold;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Inter', sans-serif;
            }
            .review-info {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                width: 100%;
            }
            .user-name {
                font-weight: 600;
                color: #2563eb;
                margin: 0;
            }
            .review-stars {
                color: #fbbf24;
                font-size: 0.9rem;
                margin: 2px 0;
            }
            .review-time {
                font-size: 0.75rem;
                color: gray;
            }
            .review-content {
                font-size: 0.95rem;
                color: #374151;
            }
            .edit-link {
                font-size: 0.85rem;
                color: #2563eb;
                text-decoration: none;
            }
            .edit-link:hover {
                color: #1d4ed8;
            }
            .nav-btn {
                color: #2563eb !important;
            }
            .swiper-wrapper {
                padding-bottom: 20px;
            }
            .modal-bg {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 9999; /* Higher than navbar */
                animation: fadeIn 0.3s ease-in-out;
            }
            .modal-bg.active {
                display: flex;
            }
            .modal-content {
                background: white;
                padding: 30px;
                width: 100%;
                max-width: 600px;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                animation: scaleUp 0.3s ease-in-out;
            }
            .button-group {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                margin-top: 1.5rem;
                padding-top: 1rem;
                border-top: 1px solid #e5e7eb;
            }
            .btn-cancel {
                padding: 10px 20px;
                background-color: #fee2e2;
                color: #dc2626;
                font-weight: 600;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: background-color 0.15s ease-in-out;
            }
            .btn-cancel:hover {
                background-color: #fecaca;
            }
            .btn-submit {
                padding: 10px 20px;
                background-color: #2563eb;
                color: white;
                font-weight: 600;
                border: none;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                cursor: pointer;
                transition: background-color 0.2s ease-in-out;
            }
            .btn-submit:hover {
                background-color: #1d4ed8;
            }

            @keyframes scaleUp {
                from {
                    transform: scale(0.95);
                    opacity: 0;
                }
                to {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            @keyframes fadeIn {
                from {
                    background-color: rgba(0, 0, 0, 0);
                }
                to {
                    background-color: rgba(0, 0, 0, 0.5);
                }
            }
            @media (max-width: 640px) {
                .modal-content {
                    padding: 20px;
                    width: 90%;
                }
            }
        </style>
    @endpush

    <div class="how-section padding-top padding-bottom">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="section-header">
                        <h1 style="font-size: xx-large; font-weight: bold;" class="title">{{ __(@$trustpilotReviewContent->data_values->heading) }}</h1>
                        <p style="font-size: small;">{{ __(@$trustpilotReviewContent->data_values->subheading) }}</p>
                    </div>
                </div>
            </div>
            <div style="overflow-x: hidden">
                {{--  @php echo gs('trustpilot_widget_code'); @endphp  --}}

                <section class="customer-review">
                    <div class="container">
                        <div class="row">
                            <div class="reviews col-12 mb-4">

                                <div class="review-header">
                                    <div>
                                        <h3>My Reviews</h3>
                                        <div class="rating-summary">
                                            <div class="flex items-center gap-2 text-yellow-500" id="review-stats">
                                                <span id="average-score">{{ number_format($average, 1) }}</span>

                                                <span id="average-stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <span class="stars">{{ $i <= $average ? '★' : '☆' }}</span>
                                                    @endfor
                                                </span>

                                                <span class="text-gray-500 text-sm review-count" id="review-count">
                                                    ({{ number_format($count) }} Reviews)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <button onclick="openModal()" class="leave-review-btn">Leave a review</button>
                                </div>

                                <!-- Swiper Slider -->
                                <div class="swiper mySwiper">
                                    <div class="swiper-wrapper">
                                        @foreach($reviews as $review)
                                            <div class="swiper-slide review-slide"> <!-- 👈 This class is required -->
                                                <div class="review-header">
                                                    <div class="avatar">{{ strtoupper(substr($review->name, 0, 2)) }}</div>

                                                    <div class="review-info">
                                                        <div>
                                                            <p class="user-name">{{ $review->name }}</p>
                                                            <p class="review-stars">
                                                                @for($i = 1; $i <= 5; $i++)
                                                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                                                @endfor
                                                            </p>
                                                            <p class="review-time">{{ $review->created_at->diffForHumans() }}</p>
                                                        </div>

                                                        @if($review->user_id && auth()->id() == $review->user_id)
                                                            <a href="javascript:void(0)" onclick="openModal()" class="edit-link">
                                                                <i class="fas fa-pencil-alt"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>

                                                <p class="review-content">{{ \Illuminate\Support\Str::limit($review->content, 250, '...') }}</p>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="swiper-button-next nav-btn"></div>
                                    <div class="swiper-button-prev nav-btn"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Leave a review modal -->
    <div class="modal-bg" id="reviewModal">
        <div class="modal-content">
            <form id="reviewForm">
                @csrf
                <h3 class="text-lg font-bold mb-4">Leave a Review</h3>

                <input style="width: -webkit-fill-available;" type="text" id="name" name="name" required placeholder="Your Name"
                        class="w-100 mb-4 p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        value="{{ auth()->check() ? auth()->user()->firstname . ' ' . auth()->user()->lastname : '' }}"/>
                <div class="text-red-500 text-sm mb-1" id="error-name"></div>

                <input style="width: -webkit-fill-available;" type="text" id="email" name="email" required placeholder="Your Email"
                        class="w-100 mb-4 p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        value="{{ auth()->check() ? auth()->user()->email : '' }}"/>
                <div class="text-red-500 text-sm mb-1" id="error-email"></div>

                <select name="rating" id="rating" required
                    class="w-100 mb-4 p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    <option value="">Select Rating</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
                <div class="text-red-500 text-sm mb-1" id="error-rating"></div>

                <textarea name="content" id="content" required placeholder="Your review..."
                    class="w-100 mb-4 p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400 focus:outline-none" rows="4"></textarea>
                <div class="text-red-500 text-sm mb-1" id="error-content"></div>

                <div class="button-group">
                    <button type="button" onclick="closeModal()" class="btn-cancel">
                        ✖ Cancel
                    </button>
                    <button type="submit" class="btn-submit">
                        ✅ Submit
                    </button>
                </div>
            </form>
            <div class="text-green-600 text-sm mt-2 hidden" id="success-msg">Review submitted!</div>
        </div>
    </div>

    @push('script')
        <!-- Swiper JS -->
        <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>

        <script>
            "use strict";
            (function($) {
                setTimeout(() => {
                    $('body').find(".commonninja-ribbon-link").remove();
                }, 1000);
            })(jQuery);

            // leave review js code start
           window.swiper = new Swiper(".mySwiper", {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                autoplay: {
                    delay: 10000,
                    disableOnInteraction: false
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2
                    },
                    1024: {
                        slidesPerView: 3
                    }
                }
            });

            function openModal() {
                fetch(`/review/auth-user`).then(async res => {
                    if (!res.ok) {
                        const errorData = await res.json();
                        // gracefully handle backend error here
                        console.warn('Backend error:', errorData);
                        return null; // exit early
                    }

                    return res.json(); // success
                })
                .then(data => {
                    if (!data) return; // no data, exit safely

                    // Fill form with review data
                    $('#reviewForm input[name="name"]').val(data.name);
                    $('#reviewForm input[name="email"]').val(data.email);
                    $('#reviewForm select[name="rating"]').val(data.rating);
                    $('#reviewForm textarea[name="content"]').val(data.content);
                })
                .catch(error => {
                    console.warn('Backend error:', error);
                    return null;
                });

                document.getElementById('reviewModal').classList.add('active');
            }

            function closeModal() {
                document.getElementById('reviewModal').classList.remove('active');
                document.getElementById('reviewForm').reset();
                document.getElementById('success-msg').classList.add('hidden');
                ['name', 'rating', 'content'].forEach(field => {
                document.getElementById('error-' + field).innerText = '';
                });
            }

            document.getElementById('reviewForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const csrfToken = document.querySelector('input[name="_token"]').value;

                fetch("{{ route('reviews.store') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
                    },
                    body: formData
                })
                .then(res => {
                    if (!res.ok) return res.json().then(err => Promise.reject(err));
                    return res.json();
                })
                .then(data => {
                    const review = data.review;

                    // Create new slide
                    if (review){
                        const slide = document.createElement('div');
                        slide.className = 'swiper-slide bg-white p-4 rounded-lg shadow';
                        slide.innerHTML = `
                            <div class="flex gap-3 items-center mb-2">
                            <div class="avatar">${review.name.substring(0,2).toUpperCase()}</div>
                            <div>
                                <p class="font-semibold text-blue-600">${review.name}</p>
                                <p class="text-yellow-500 text-sm">
                                ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                                </p>
                                <p class="text-gray-400 text-xs">Just now</p>
                            </div>
                            </div>
                            <p class="text-gray-700 text-sm">${review.content.substring(0, 250)}...</p>
                        `;
                        window.swiper.appendSlide(slide);
                    }

                    // Update average and count
                    document.getElementById('average-score').innerText = Number(data.average).toFixed(1);

                    const starContainer = document.getElementById('average-stars');
                    const roundedAverage = Math.round(data.average);
                    starContainer.innerHTML = '';
                    for (let i = 1; i <= 5; i++) {
                        starContainer.innerHTML += `<span class="stars">${i <= roundedAverage ? '★' : '☆'}</span>`;
                    }

                    document.getElementById('review-count').innerText = `(${data.count} Reviews)`;

                    // Success message
                    document.getElementById('success-msg').classList.remove('hidden');
                    document.getElementById('reviewForm').reset();
                    setTimeout(closeModal, 1000);
                })
                .catch(err => {
                    const errors = err.errors || {};
                    ['name', 'email', 'rating', 'content'].forEach(field => {
                        document.getElementById('error-' + field).innerText = errors[field] ? errors[field][0] : '';
                    });
                });
            });
        </script>
    @endpush

@endif

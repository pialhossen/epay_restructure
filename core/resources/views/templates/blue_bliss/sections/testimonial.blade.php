@php
    $testimonialContent = getContent('testimonial.content', true);
    $testimonialElements = getContent('testimonial.element');
@endphp
<section class="client-section padding-bottom padding-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="section-header left-style">
                    <h2 class="font-bold text-xl title">{{ __(@$testimonialContent->data_values->heading) }}</h2>
                    <p>{{ __(@$testimonialContent->data_values->subheading) }}</p>
                </div>
            </div>
        </div>
        <div class="client-slider">
            <div class="swiper-wrapper">
                @foreach ($testimonialElements->chunk(2) as $testimonialElement)
                    <div class="swiper-slide" style="display: flex; gap: 20px;">
                        @if(isset($testimonialElement[0]))
                        <div class="client-item">
                            <div class="client-thumb">
                                <div class="content">
                                    <h6 class="title">{{ __(@$testimonialElement[0]->data_values->name) }}</h6>
                                    <span>{{ __(@$testimonialElement[0]->data_values->designation) }}</span>
                                </div>
                            </div>
                            <div class="client-content">
                                <blockquote> {{ __(@$testimonialElement[0]->data_values->description) }}</blockquote>
                            </div>
                        </div>
                        @endif
                        @if(isset($testimonialElement[1]))
                        <div class="client-item">
                            <div class="client-thumb">
                                <div class="content">
                                    <h6 class="title">{{ __(@$testimonialElement[1]->data_values->name) }}</h6>
                                    <span>{{ __(@$testimonialElement[1]->data_values->designation) }}</span>
                                </div>
                            </div>
                            <div class="client-content">
                                <blockquote> {{ __(@$testimonialElement[1]->data_values->description) }}</blockquote>
                            </div>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="common-pagination"></div>
    </div>
</section>

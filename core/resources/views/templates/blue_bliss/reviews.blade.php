@extends($activeTemplate . 'layouts.frontend')
@section('content')
<style>
    body{
        background-color: rgb(226 226 226);
    }
    .review-container{
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .review{
        border: 1px solid rgb(216 216 216);
        padding: 10px;
        border-radius: 10px;
        background-color: white;
    }
    .review .review-header{
        /* color: #294396; */
    }
    .review .review-content{
        background-color: rgb(238 238 238);
        padding: 10px;
        border-radius: 5px;
    }
    .review .review-header .avatar{
        font-size: 25px;
        font-weight: 600;
        width: 50px;
        height: 50px;
        background-color: #C9C9C9;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    /* .user-name,
    .review-stars,
    .review-time
    {
        margin-bottom: .3rem;
    } */
</style>
    <div class="padding-top padding-bottom">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="">
                        <div class="review-container">
                            @foreach($reviews as $review)
                        
                                <div class="review">
                                    <div class="review-header">
                                        <div class="avatar" style="margin-bottom: 1rem;">
                                        @if($review->user->image)
                                        <img id="preview" style="border-radius: 50%; width: 50px; height: 50px;" src="{{ (APP_PUBLIC_FOLDER ? "/".APP_PUBLIC_FOLDER."/": '').$review->user->image}}" alt="" draggable="false">
                                        @else
                                        {{ strtoupper(substr($review->name, 0, 2)) }}
                                        @endif
                                        </div>

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
                                        </div>
                                    </div>

                                    <p class="review-content">
                                        {{ $review->content }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
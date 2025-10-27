<style>
    #welcomeModal {
        display: none;
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        padding: 1rem;
    }

    #welcomeModal .modal-content {
        background-color: #fff;
        border-radius: 1rem;
        padding: 2rem;
        max-width: 500px;
        width: 100%;
        position: relative;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: fadeIn 0.3s ease-in-out;
    }

    #welcomeModal .modal-image {
        width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: cover;
        border-radius: 0.75rem 0.75rem 0 0;
        margin-bottom: 1rem;
    }

    #welcomeModal .close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 1.5rem;
        color: #888;
        cursor: pointer;
        border: none;
        background: none;
    }

    #welcomeModal .close:hover {
        color: #e3342f;
    }

    .modal-btn {
        background-color: #2563eb;
        color: white;
        padding: 0.6rem 1.8rem;
        border-radius: 9999px;
        font-weight: bold;
        border: none;
        margin-top: 1.5rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .modal-btn:hover {
        background-color: #1d4ed8;
    }

    @media (max-width: 600px) {
        #welcomeModal .modal-content {
            padding: 1.5rem;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- ✅ Modal HTML -->
@if($modalDetails)
<div id="welcomeModal">
    <div class="modal-content">
        <button class="close" id="closeModalBtn">&times;</button>
        @if ($modalDetails->image != null)
            <img src="{{ asset($modalDetails->image) }}" alt="{{ $modalDetails->title ?? '' }}" class="modal-image" >
        @else
            <img src="{{ asset('assets/admin/images/login.jpg') }}" alt="" class="modal-image" >
        @endif
        <h2 class="text-xl font-bold mb-2">{{ $modalDetails->title ?? '' }}</h2>
        <p class="text-gray-700">{!! $modalDetails->description ?? '' !!}</p>
        <button class="modal-btn" id="gotItBtn">{{ $modalDetails->button_name ?? 'Got It!' }}</button>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('welcomeModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const gotItBtn = document.getElementById('gotItBtn');
        if(modal){
            modal.style.display = 'flex';
    
            // Auto-close after 2 minutes
            setTimeout(() => {
                modal.style.display = 'none';
            }, 120000);
    
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
    
            gotItBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
    });
</script>

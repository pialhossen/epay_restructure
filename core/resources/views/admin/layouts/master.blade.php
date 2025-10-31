<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="site-path" content="{{ '/'.APP_PUBLIC_FOLDER }}">
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>

    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/bootstrap-toggle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">

    @stack('style-lib')

    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    @stack('style')
    <script>
        const site_path = document.querySelector('meta[name="site-path"]').getAttribute("content")? document.querySelector('meta[name="site-path"]').getAttribute("content"): '';
        const general_settings = @json(gs())

        function updateQueryParam(key, value) {
            const url = new URL(window.location);
            url.searchParams.set(key, value);
            window.location = url.toString();
        }
        function toggleSort(e, column) {
            const url = new URL(window.location);
            const currentSort = url.searchParams.get('sort'); // e.g. "amount:asc"
            let nextSort = '';

            if (currentSort) {
                const [currentColumn, currentDirection] = currentSort.split(':');

                if (currentColumn === column) {
                    // Same column clicked → cycle direction
                    if (currentDirection === 'asc') {
                        nextSort = `${column}:desc`;
                    } else if (currentDirection === 'desc') {
                        // Remove the sort param
                        url.searchParams.delete('sort');
                        window.location = url.toString();
                        return;
                    }
                } else {
                    // Different column clicked → reset to asc
                    nextSort = `${column}:asc`;
                }
            } else {
                $element = e.currentTarget;
                nextSort = `${column}:asc`;
            }

            // Update URL
            url.searchParams.set('sort', nextSort);
            window.location = url.toString();
        }


        
        window.stopAlertNotificationBroadcast = "{{ route('api.stop_alert') }}";
        window.APP_PUBLIC_FOLDER = "{{ APP_PUBLIC_FOLDER }}";
        window.exchange_advance_search = "{{ route('admin.exchange.advance.search') }}";
        window.current_url = "{{ url()->current() }}"
    </script>
    @php
        $customView = 'components.custom.custom';
    @endphp

    @if (View::exists($customView))
        <x-custom.custom />
    @endif
</head>

<body>

    @yield('content')

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/bootstrap-toggle.min.js') }}"></script>


    @include('partials.notify')
    @stack('script-lib')

    <script src="{{ asset('assets/global/js/nicEdit.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/app.js') }}"></script>
    @vite(['resources/js/app.js'])

    {{-- LOAD NIC EDIT --}}
    <script>
        "use strict";

        bkLib.onDomLoaded(function() {
            $(".nicEdit").each(function(index) {
                $(this).attr("id", "nicEditor" + index);
                new nicEditor({
                    fullPanel: true
                }).panelInstance('nicEditor' + index, {
                    hasPanel: true
                });
            });
        });
        (function($) {
            $(document).on('mouseover ', '.nicEdit-main,.nicEdit-panelContain', function() {
                $('.nicEdit-main').focus();
            });
        })(jQuery);

        (function($) {


            $('.breadcrumb-nav-open').on('click', function() {
                $(this).toggleClass('active');
                $('.breadcrumb-nav').toggleClass('active');
            });

            $('.breadcrumb-nav-close').on('click', function() {
                $('.breadcrumb-nav').removeClass('active');
            });

            if ($('.topTap').length) {
                $('.breadcrumb-nav-open').removeClass('d-none');
            }
        })(jQuery);
    </script>

    @stack('script')

</body>

</html>

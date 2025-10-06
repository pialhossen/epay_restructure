// import './bootstrap.js';
import "../css/app.css";
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";
import Swal from "sweetalert2";

window.Echo.private("check.admin").listen(
    ".exchange.notification",
    function (e) {
        const sound = general_settings
            ? `/${general_settings.exchange_notification}`
            : "/assets/sound/default_exchange_notification.mp3";
        const audio = new Audio(sound);
        audio.volume = 1.0;

        if (exchange_alert_btn_status) {
            audio.loop = true;
            audio.play();
            Swal.fire({
                title: "New Order Placed!",
                text: "A new order has been placed click redirect to view order details.",
                icon: "success",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "View Details",
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(`/admin/exchange/details/${e.exchange.id}`, '_blank');
                }
                audio.pause();
                audio.currentTime = 0;
            });
        }

    }
);

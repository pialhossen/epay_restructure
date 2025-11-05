// import './bootstrap.js';
import "../css/app.css";
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";
import Swal from "sweetalert2";

const sound = general_settings
            ? `/${window.APP_PUBLIC_FOLDER? window.APP_PUBLIC_FOLDER +'/': ''}${general_settings.exchange_notification}`
            : `/${window.APP_PUBLIC_FOLDER? window.APP_PUBLIC_FOLDER +'/': ''}assets/sound/default_exchange_notification.mp3`;
window.EXCHANGE_ALERT_AUDIO = new Audio(sound);

window.Echo.private("check.admin").listen(
    ".exchange.notification",
    function (e) {
        window.EXCHANGE_ALERT_AUDIO.volume = 1.0;

        if (exchange_alert_btn_status) {
            window.EXCHANGE_ALERT_AUDIO.loop = true;
            window.EXCHANGE_ALERT_AUDIO.play();
            Swal.fire({
                title: "New Order Placed!",
                text: "A new order has been placed click redirect to view order details.",
                icon: "success",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "View Details",
            }).then((result) => {
                fetch(window.stopAlertNotificationBroadcast, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        command: "stop-alert",
                        message: "stop exchange alert"
                    })
                })
                .then(res => res.json())
                .then(data => console.log(data))
                .catch(error => console.error('Error:', error))

                if (result.isConfirmed) {
                    window.open(`/${window.APP_PUBLIC_FOLDER? window.APP_PUBLIC_FOLDER +'/': ''}admin/exchange/details/${e.exchange.id}`, '_blank');
                }
            })
        }

    }
);
window.Echo.private("check.admin").listen(
    ".exchange.stop.notification",
    function(e){
        console.log('Alert Stop Broadcast Received')
        window.EXCHANGE_ALERT_AUDIO.pause();
        window.EXCHANGE_ALERT_AUDIO.currentTime = 0;
    }
)
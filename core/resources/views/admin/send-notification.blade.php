@extends('admin.layouts.app')
@section('panel')
    <style>
        .base-timer {
            position: relative;
            width: 300px;
            height: 300px;
        }

        .base-timer__svg {
            transform: scaleX(-1);
        }

        .base-timer__circle {
            fill: none;
            stroke: none;
        }

        .base-timer__path-elapsed {
            stroke-width: 7px;
            stroke: grey;
        }

        .base-timer__path-remaining {
            stroke-width: 7px;
            stroke-linecap: round;
            transform: rotate(90deg);
            transform-origin: center;
            transition: 1s linear all;
            fill-rule: nonzero;
            stroke: currentColor;
        }
        .base-timer__path-remaining {
            stroke-width: 7px;
            stroke-linecap: round;
            fill: none;
            
            /* Required for the dashoffset technique */
            stroke-dasharray: 283; 
            
            /* CRITICAL FOR SMOOTH ANIMATION AND CLEAN FINISH */
            transition: stroke-dashoffset 0.5s linear; 
        }

        .base-timer__path-remaining.green {
            color: rgb(65, 184, 131);
        }

        .base-timer__path-remaining.orange {
            color: orange;
        }

        .base-timer__path-remaining.red {
            color: red;
        }

        .base-timer__label {
            position: absolute;
            width: 300px;
            height: 300px;
            top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .timer-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            place-items: center;
            gap: 20px;
        }

        .timer-desc {
            display: flex;
            gap: 20px;
        }

        .timer-desc .success-text {
            color: green;
        }

        .timer-desc .failed-text {
            color: red;
        }

        #current-notification {
            color: black;
        }
        #transmission_complete{
            color: green;
        }
        .timer-container{
            font-size: 25px;
        }
    </style>

    <div class="timer-container">
        <div id="timer"></div>
        <div><span id="current-notification">1</span>/ {{ count($user_ids) }} Proccessing</div>
        <div>
            <button ></button>
        </div>
        <div class="timer-desc">
            <span class="success-text"><span id="successful-notification">0</span> Successful
                {{ $notification_type }}</span>
            <span class="failed-text"><span id="failed-notification">0</span> Failed {{ $notification_type }}</span>
        </div>
        <h2 id="transmission_complete" style="display: none;">Transmission Complete</h2>
    </div>

    <script>
        // --- Template Variables (Assume these are set by your backend, e.g., Blade/PHP) ---
        const user_ids = @json($user_ids);
        const message = "{{ $message }}";
        const subject = "{{ $subject }}";
        const notification_type = "{{ $notification_type }}";
        const image_url = "{{ $imageUrl }}";
        let user_index = 0;
        let failed = 0;
        let success = 0;

        const current_notification_indicator = document.querySelector('#current-notification');
        const successful_notification_indicator = document.querySelector('#successful-notification');
        const failed_notification_indicator = document.querySelector('#failed-notification');

        // --- Constants for Timer Logic ---
        const FULL_DASH_ARRAY = 283; // Circumference of the circle (2 * PI * 45)
        const TIME_LIMIT = 5;       // Total time in seconds before the timer resets/performs action
        const ALERT_THRESHOLD = TIME_LIMIT / 4;
        const WARNING_THRESHOLD = TIME_LIMIT / 2;

        // --- Color Code Configuration ---
        const COLOR_CODES = {
            info: { color: "green" },
            warning: { color: "orange", threshold: WARNING_THRESHOLD },
            alert: { color: "red", threshold: ALERT_THRESHOLD },
        };

        // --- State Variables ---
        let timePassed = 0;
        let timeLeft = TIME_LIMIT;
        let timerInterval = null;
        let remainingPathColor = COLOR_CODES.info.color;

        function updateFrontend() {
            if (user_index < user_ids.length) {
                current_notification_indicator.innerHTML = (user_index + 1);
                failed_notification_indicator.innerHTML = failed;
                successful_notification_indicator.innerHTML = success;
            } else {
                // Handle completion state if needed (e.g., show total count)
                current_notification_indicator.innerHTML = user_ids.length;
                successful_notification_indicator.innerHTML = success;
                failed_notification_indicator.innerHTML = failed;
                document.querySelector('#transmission_complete').style.display = "block";
            }
        }

        // 1. Initial Render of the Timer HTML Structure (No change, uses FULL_DASH_ARRAY as reference)
        document.getElementById("timer").innerHTML = `
            <div class="base-timer">
              <svg class="base-timer__svg" viewBox="0 0 100 100">
                <g class="base-timer__circle">
                  <circle class="base-timer__path-elapsed" cx="50" cy="50" r="45"></circle>
                  <path
                    id="base-timer-path-remaining"
                    stroke-dasharray="${FULL_DASH_ARRAY}" 
                    class="base-timer__path-remaining ${remainingPathColor}"
                    d="
                      M 50, 50
                      m -45, 0
                      a 45,45 0 1,0 90,0
                      a 45,45 0 1,0 -90,0
                    "
                  ></path>
                </g>
              </svg>
              <span id="base-timer-label" class="base-timer__label">${formatTime(timeLeft)}</span>
            </div>
        `;

        // 2. Function to Execute When Timer Hits Zero
        function onTimesUp() {
            clearInterval(timerInterval);

                // CRITICAL FIX: Manually set the offset to FULL_DASH_ARRAY (hidden) 
                // before the fetch starts to show a guaranteed empty circle.
                document.getElementById("base-timer-path-remaining").setAttribute("stroke-dashoffset", FULL_DASH_ARRAY);

                console.log('Fetching now with user ID:', user_ids[user_index]);

                document.querySelector('#base-timer-label').innerHTML = "Sending"

                fetch('{{ route('admin.users.notification.single.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        user_id: user_ids[user_index],
                        message: message,
                        subject: subject,
                        via: notification_type,
                        imageUrl: image_url,
                    })
                })
                
                    .then(response => response.json())
                    .then(data => {
                        document.querySelector('#base-timer-label').innerHTML = formatTime(timeLeft);
                        console.log('Fetch successful for user:', user_ids[user_index], data);

                        if (data.status === "success") {
                            success++;
                        } else {
                            failed++;
                        }
                        user_index++;

                        updateFrontend();

                        // Only restart the timer if there are more users
                        if (user_index < user_ids.length) {
                            resetTimer();
                            startTimer();
                        } else {
                            console.log('All notifications sent. Stopping timer.');
                            // You might want to display a final message here
                        }
                    })
                    .catch(error => {
                        document.querySelector('#base-timer-label').innerHTML = formatTime(timeLeft);
                        console.error('Fetch failed for user:', user_ids[user_index], error);
                        // Assume failure means we still need to move to the next user
                        failed++;
                        user_index++;
                        updateFrontend();

                        if (user_index < user_ids.length) {
                            resetTimer();
                            startTimer();
                        } else {
                            console.log('All notifications attempted. Stopping timer.');
                        }
                    });
                
        }

        // 3. Main Timer Control Function
        function startTimer() {
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timePassed += 1;
                timeLeft = TIME_LIMIT - timePassed;

                document.getElementById("base-timer-label").innerHTML = formatTime(timeLeft);
                setCircleDashoffset(timeLeft); // *** NEW FUNCTION CALL ***
                setRemainingPathColor(timeLeft);

                if (timeLeft <= 0) {
                    onTimesUp();
                }
            }, 1000);
        }

        // 4. Function to Reset State
        function resetTimer() {
            clearInterval(timerInterval);
            timePassed = 0;
            timeLeft = TIME_LIMIT;

            const path = document.getElementById("base-timer-path-remaining");

            path.classList.remove(COLOR_CODES.warning.color, COLOR_CODES.alert.color);
            path.classList.add(COLOR_CODES.info.color);

            // *** FIX: Reset offset to 0 (full circle visible) ***
            path.setAttribute("stroke-dashoffset", 0);

            document.getElementById("base-timer-label").innerHTML = formatTime(timeLeft);
        }

        // 5. Time Formatting Helper (No Change)
        function formatTime(time) {
            const minutes = Math.floor(time / 60);
            let seconds = time % 60;
            if (seconds < 10) seconds = `0${seconds}`;
            return `${minutes}:${seconds}`;
        }

        // 6. Color Update Logic (No Change)
        function setRemainingPathColor(timeLeft) {
            const { alert, warning, info } = COLOR_CODES;
            const path = document.getElementById("base-timer-path-remaining");

            if (timeLeft <= alert.threshold) {
                path.classList.remove(warning.color, info.color);
                path.classList.add(alert.color);
            } else if (timeLeft <= warning.threshold) {
                path.classList.remove(alert.color, info.color);
                path.classList.add(warning.color);
            } else {
                path.classList.remove(warning.color, alert.color);
                path.classList.add(info.color);
            }
        }

        // 7. CRITICAL FIX: New Logic using stroke-dashoffset
        function calculateTimeFraction() {
            // We ensure the fraction is always between 0 and 1, mapping time 0 to fraction 0.
            // The subtraction (1 - ...) is no longer needed here as offset logic handles it.
            return Math.max(0, timeLeft / TIME_LIMIT);
        }

        function setCircleDashoffset(time) {
            const timeFraction = calculateTimeFraction();

            // Offset Calculation: 
            // We calculate the fraction of time *elapsed* (1 - fraction_remaining).
            // At the start (timeFraction=1), elapsed is 0, offset is 0.
            // At the end (timeFraction=0), elapsed is 1, offset is FULL_DASH_ARRAY (283).
            const dashOffset = FULL_DASH_ARRAY * (1 - timeFraction);

            // Apply the offset. Using .toFixed(0) ensures a clean integer value, which
            // helps prevent the final visual bug.
            document
                .getElementById("base-timer-path-remaining")
                .setAttribute("stroke-dashoffset", dashOffset.toFixed(0));
        }


        // --- Initialization ---
        updateFrontend();
        // Set initial offset to 0 (full circle visible)
        setCircleDashoffset(TIME_LIMIT);
        // Start the timer loop
        startTimer(); 
    </script>
@endsection
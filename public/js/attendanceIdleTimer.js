document.addEventListener('DOMContentLoaded', () => {
    const timer = document.getElementById('attendance-idle-timer');
    if (!timer) {
        return;
    }

    // Adjust these durations in minutes; values are converted to milliseconds.
    const idleDelay = 5 * 60 * 1000;
    const countdownDuration = 30 * 60 * 1000;
    const activityKey = `attendance-idle-${timer.dataset.sessionId}`;
    let lastActivity = Date.now();
    let closeInProgress = false;
    let phaseTimer = null;
    let retryTimer = null;

    try {
        const storedActivity = Number(localStorage.getItem(activityKey));
        if (Number.isFinite(storedActivity) && storedActivity > 0) {
            lastActivity = storedActivity;
        } else {
            localStorage.setItem(activityKey, String(lastActivity));
        }
    } catch (error) {
        console.error('Could not persist attendance timer activity.', error);
    }

    function recordActivity() {
        lastActivity = Date.now();
        try {
            localStorage.setItem(activityKey, String(lastActivity));
        } catch (error) {
            console.error('Could not persist attendance timer activity.', error);
        }
        closeInProgress = false;
        if (phaseTimer !== null) {
            window.clearTimeout(phaseTimer);
            phaseTimer = null;
        }
        if (retryTimer !== null) {
            window.clearTimeout(retryTimer);
            retryTimer = null;
        }
        scheduleNextPhase();
    }

    window.addEventListener('storage', (event) => {
        if (event.key === activityKey && event.newValue) {
            const sharedActivity = Number(event.newValue);
            if (Number.isFinite(sharedActivity) && sharedActivity > lastActivity) {
                lastActivity = sharedActivity;
                closeInProgress = false;
                if (phaseTimer !== null) {
                    window.clearTimeout(phaseTimer);
                }
                if (retryTimer !== null) {
                    window.clearTimeout(retryTimer);
                    retryTimer = null;
                }
                scheduleNextPhase();
            }
        }
    });

    ['pointerdown', 'keydown', 'input', 'touchstart', 'submit'].forEach((eventName) => {
        document.addEventListener(eventName, recordActivity, true);
    });
    window.addEventListener('attendance-input', recordActivity);

    async function closeSession() {
        if (closeInProgress) {
            return;
        }
        closeInProgress = true;
        try {
            const response = await fetch(timer.dataset.endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': timer.dataset.csrfToken,
                    Accept: 'application/json'
                }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.error || 'The attendance session could not be closed.');
            }
            try {
                localStorage.removeItem(activityKey);
            } catch (error) {
                console.error('Could not clear attendance timer activity.', error);
            }
            window.location.reload();
        } catch (error) {
            closeInProgress = false;
            console.error('Unable to close the idle attendance session.', error);
            window.alert('The attendance session could not be closed automatically. Please check your connection and try again.');
            retryTimer = window.setTimeout(() => {
                retryTimer = null;
                scheduleNextPhase();
            }, 60 * 1000);
        }
    }

    function scheduleNextPhase() {
        if (closeInProgress) {
            return;
        }
        if (phaseTimer !== null) {
            window.clearTimeout(phaseTimer);
        }

        const elapsed = Date.now() - lastActivity;
        if (elapsed >= idleDelay + countdownDuration) {
            closeSession();
        } else if (elapsed < idleDelay) {
            phaseTimer = window.setTimeout(scheduleNextPhase, idleDelay - elapsed);
        } else {
            phaseTimer = window.setTimeout(closeSession, idleDelay + countdownDuration - elapsed);
        }
    }

    scheduleNextPhase();
});

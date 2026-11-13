(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    document.querySelectorAll('[data-newsticker]').forEach((ticker) => {
        const viewport = ticker.querySelector('.svbs-ticker__viewport');
        const track = ticker.querySelector('.svbs-ticker__track');
        if (!viewport || !track || !track.animate || !window.ResizeObserver) {
            return;
        }

        let animation;
        let hovered = false;
        let focused = false;

        const updatePlayback = () => {
            if (!animation) {
                return;
            }
            if (hovered || focused || document.hidden) {
                animation.pause();
            } else {
                animation.play();
            }
        };

        const rebuild = () => {
            const previousX = animation
                ? new DOMMatrixReadOnly(getComputedStyle(track).transform).m41
                : 0;
            animation?.cancel();
            animation = undefined;
            ticker.classList.remove('svbs-ticker--running');
            if (reducedMotion.matches) {
                return;
            }

            ticker.classList.add('svbs-ticker--running');
            const width = viewport.clientWidth;
            const trackWidth = track.scrollWidth;
            if (!width || !trackWidth) {
                ticker.classList.remove('svbs-ticker--running');
                return;
            }

            // Keep a consistent reading speed, regardless of the number of messages.
            const duration = ((width + trackWidth) / 60) * 1000;
            animation = track.animate([
                { transform: `translateX(${width}px)` },
                { transform: `translateX(-${trackWidth}px)` },
            ], {
                duration,
                iterations: Infinity,
                easing: 'linear',
            });
            // Start with readable text and keep it visible when resizing a paused ticker.
            const position = Math.max(-trackWidth + Math.min(width, trackWidth), Math.min(previousX, 0));
            animation.currentTime = ((width - position) / (width + trackWidth)) * duration;
            updatePlayback();
        };

        viewport.addEventListener('pointerenter', (event) => {
            hovered = event.pointerType === 'mouse';
            updatePlayback();
        });
        viewport.addEventListener('pointerleave', () => {
            hovered = false;
            updatePlayback();
        });
        viewport.addEventListener('focusin', () => {
            focused = true;
            updatePlayback();
        });
        viewport.addEventListener('focusout', () => {
            focused = false;
            updatePlayback();
        });
        document.addEventListener('visibilitychange', updatePlayback);
        reducedMotion.addEventListener('change', rebuild);

        let previousWidth = -1;
        new ResizeObserver(() => {
            if (viewport.clientWidth !== previousWidth) {
                previousWidth = viewport.clientWidth;
                rebuild();
            }
        }).observe(viewport);
        document.fonts?.ready.then(rebuild);
        rebuild();
    });
})();

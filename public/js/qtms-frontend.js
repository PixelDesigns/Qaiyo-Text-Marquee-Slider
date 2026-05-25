(function () {
    'use strict';

    function initMarquee(container) {
        if (container.dataset.qtmsReady) return;
        container.dataset.qtmsReady = '1';

        var rows = container.querySelectorAll('.qtms-row');
        for (var i = 0; i < rows.length; i++) {
            initRow(rows[i]);
        }
    }

    function initRow(row) {
        var track = row.querySelector('.qtms-track');
        if (!track) return;

        var direction = row.getAttribute('data-direction') || 'left';
        var speed = parseInt(row.getAttribute('data-speed'), 10) || 30;
        var original = track.querySelector('.qtms-track-copy');

        if (!original) return;

        // Wait for fonts to be ready before measuring
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () {
                setupTrack(row, track, original, direction, speed);
            });
        } else {
            // Fallback: wait a bit for fonts
            setTimeout(function () {
                setupTrack(row, track, original, direction, speed);
            }, 200);
        }
    }

    function setupTrack(row, track, original, direction, speed) {
        // Clean up any previous clones
        var existing = track.querySelectorAll('.qtms-track-copy');
        for (var i = existing.length - 1; i > 0; i--) {
            track.removeChild(existing[i]);
        }

        // Remove animation while measuring
        track.classList.remove('qtms-animate');
        track.style.animation = 'none';
        track.style.transform = 'none';

        // Force reflow so we get accurate measurement
        void track.offsetHeight;

        var copyWidth = original.getBoundingClientRect().width;

        if (copyWidth === 0) {
            // Not rendered yet — retry
            setTimeout(function () {
                setupTrack(row, track, original, direction, speed);
            }, 300);
            return;
        }

        var viewportWidth = row.offsetWidth;

        // Need enough copies to fill viewport + one extra for seamless loop
        var neededCopies = Math.ceil(viewportWidth / copyWidth) + 2;
        if (neededCopies < 2) neededCopies = 2;

        for (var c = 1; c < neededCopies; c++) {
            var clone = original.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.setAttribute('data-nosnippet', '');
            if ('inert' in clone) {
                clone.inert = true;
            }
            var clonedLinks = clone.querySelectorAll('a');
            for (var j = 0; j < clonedLinks.length; j++) {
                clonedLinks[j].setAttribute('tabindex', '-1');
                clonedLinks[j].setAttribute('aria-hidden', 'true');
            }
            track.appendChild(clone);
        }

        // Re-measure after clones are added (should be same, but be safe)
        var singleWidth = original.getBoundingClientRect().width;

        // Calculate animation
        var pps = speed * 2;
        var duration = singleWidth / pps;

        // Reset styles
        track.style.animation = '';
        track.style.transform = '';

        if (direction === 'left') {
            track.style.setProperty('--qtms-from', '0px');
            track.style.setProperty('--qtms-to', '-' + singleWidth + 'px');
        } else {
            track.style.setProperty('--qtms-from', '-' + singleWidth + 'px');
            track.style.setProperty('--qtms-to', '0px');
        }

        track.style.animationDuration = duration + 's';

        // Force reflow before adding animation class
        void track.offsetHeight;
        track.classList.add('qtms-animate');
    }

    function scanAndInit() {
        var marquees = document.querySelectorAll('.qtms-marquee');
        for (var i = 0; i < marquees.length; i++) {
            initMarquee(marquees[i]);
        }
    }

    // Immediate scan
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scanAndInit);
    } else {
        scanAndInit();
    }

    // Delayed scan for async page builders (Bricks, Oxygen, etc.)
    setTimeout(scanAndInit, 500);
    setTimeout(scanAndInit, 1500);

    // MutationObserver: catch dynamically inserted marquees
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    if (added[j].nodeType !== 1) continue;
                    if (added[j].classList && added[j].classList.contains('qtms-marquee')) {
                        initMarquee(added[j]);
                    }
                    // Also check children
                    if (added[j].querySelectorAll) {
                        var nested = added[j].querySelectorAll('.qtms-marquee');
                        for (var k = 0; k < nested.length; k++) {
                            initMarquee(nested[k]);
                        }
                    }
                }
            }
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    // Resize handler
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            var marquees = document.querySelectorAll('.qtms-marquee');
            for (var i = 0; i < marquees.length; i++) {
                marquees[i].removeAttribute('data-qtms-ready');
                initMarquee(marquees[i]);
            }
        }, 250);
    });
})();

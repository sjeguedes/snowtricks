import coords from './element-coords';
export default (element, adjustYPosition = null) => { // HHTMLNodeElement, + or - integer
    // Pure JavaScript cross browser smooth scroll:
    // https://www.youtube.com/watch?v=hPT1SSHptWA
    // Code inspired from:
    // https://codepen.io/Coding_Journey/pen/xyjwpr
    // To have the correct position, this must be updated with:
    // https://www.kirupa.com/html5/get_element_position_using_javascript.htm
    // Easing Functions:
    // http://gizma.com/easing/
    // requestAnimationFrame:
    // https://developer.mozilla.org/fr/docs/Web/API/Window/requestAnimationFrame
    // Check page reload:
    // https://stackoverflow.com/questions/5004978/check-if-page-gets-reloaded-or-refreshed-in-javascript/53307588#53307588
    // https://developer.mozilla.org/en-US/docs/Web/API/PerformanceNavigation
    // https://developer.mozilla.org/en-US/docs/Web/API/Navigation_timing_API
    // https://developer.mozilla.org/en-US/docs/Web/API/PerformanceNavigationTiming/type

    // Scroll to form automatically when a form is not valid.
    const targetPosition = coords(element).y + adjustYPosition;
    let startPosition = window.pageYOffset;
    let distance = targetPosition - startPosition;
    // Avoid inverted scroll behavior in certain browsers if smoothScroll is already done.
    if (window.performance && (performance.navigation.TYPE_RELOAD || 'reload' === performance.getEntriesByType("navigation")[0].type)) {
        if (0 > distance) {
            distance = - distance;
            startPosition = 0;
        }
    }
    const duration = 1000;
    let start = null;
    let args = [startPosition, distance, duration];
    // Easing
    const easeInOutCubic = function(t, b, c, d) {
        t /= d/2;
        if (t < 1) return c/2*t*t*t + b;
        t -= 2;
        return c/2*(t*t*t + 2) + b;
    };
    // Set a step for requestAnimationFrame
    const step = function(timestamp) { // DOMHighResTimeStamp object as argument
        if (!start) start = timestamp;
        const progress = timestamp - start;
        // Remove first item from "args"
        if (4 === args.length) args.shift();
        // Add "progress" as first item in "args"
        args.unshift(progress);
        window.scrollTo(0, easeInOutCubic(...args)); // [progress, startPosition, distance, duration]
        // Recursive scrolling animation
        if (progress < duration) window.requestAnimationFrame(step);
    };
    // Get scrolling animation
    window.requestAnimationFrame(step);
}
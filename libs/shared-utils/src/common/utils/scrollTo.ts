import BezierEasing from 'bezier-easing';

const fps = 60;

interface ScrollToOptions {
  endPos: number;
  cb?: () => void;
  animation?: string | [number, number, number, number];
  duration?: number;
  container?: Window | HTMLElement;
}

const predefinedAnimations: Record<string, [number, number, number, number]> = {
  ease: [0.25, 0.1, 0.25, 1],
  linear: [0, 0, 1, 1],
  'ease-in': [0.42, 0, 1, 1],
  'ease-out': [0, 0, 0.58, 1],
  'ease-in-out': [0.42, 0, 0.58, 1],
};

/**
 * Smoothly scrolls to a specified position within a container using Bezier easing functions.
 * 
 * This function animates the scroll position of a container (or the window) to a given `endPos` 
 * over a specified `duration` with customizable easing animations. It uses `BezierEasing` to 
 * handle different easing curves, and provides callbacks when the animation is completed.
 * 
 * @param {ScrollToOptions} options - The options for configuring the scroll animation.
 * @param {number} options.endPos - The final scroll position (in pixels) to scroll to.
 * @param {() => void} [options.cb] - An optional callback function to execute once the scroll animation is complete.
 * @param {string | [number, number, number, number]} [options.animation='ease'] - The easing animation to use, either as a predefined string or a custom Bezier curve.
 * @param {number} [options.duration=1000] - The duration of the scroll animation, in milliseconds.
 * @param {Window | HTMLElement} [options.container=window] - The container to scroll. Defaults to the `window`.
 * 
 * @example
 * // Example 1: Scroll to position 500 with default ease animation
 * scrollTo({ endPos: 500 });
 * 
 * @example
 * // Example 2: Scroll to position 500 with linear animation and a callback
 * scrollTo({ endPos: 500, animation: 'linear', cb: () => console.log('Scroll complete!') });
 * 
 * @example
 * // Example 3: Scroll within an element (not the window) with custom duration and ease-in-out animation
 * const container = document.getElementById('scrollable-container');
 * scrollTo({ endPos: 300, duration: 1500, animation: 'ease-in-out', container });
 */
export const scrollTo = ({
  endPos,
  cb,
  animation = 'ease',
  duration = 1000,
  container = window,
}: ScrollToOptions): void => {
  const step = 1000 / fps;

  let bezierVals: [number, number, number, number] = [0.25, 0.1, 0.25, 1];
  let timeTaken = 0;

  // Determine the Bezier curve values based on the animation option
  if (typeof animation === 'string') {
    bezierVals = predefinedAnimations[animation] || predefinedAnimations['ease'];
  } else if (Array.isArray(animation)) {
    bezierVals = animation;
  }

  // Create the easing function using BezierEasing
  const easing = BezierEasing(...bezierVals);

  // Determine the starting position and the difference to scroll
  // @ts-ignore
  const startPos = container === window ? container.pageYOffset : container.scrollTop;// TODO: check scrollTop usage
  const scrollDiff = endPos - startPos;

  // Animate the scrolling in steps until the duration is complete
  while (timeTaken <= duration) {
    (function(timeTaken) {
      window.setTimeout(() => {
        container.scrollTo(
          0,
          startPos + easing(timeTaken / duration) * scrollDiff
        );

        // Call the callback if provided and the animation is complete
        if (cb && timeTaken + step > duration) {
          cb();
        }
      }, step + timeTaken);
    })(timeTaken);

    timeTaken += step;
  }
};


import BezierEasing from 'bezier-easing';

const fps = 60;

const predefinedAnimations = {
  ease: [0.25, 0.1, 0.25, 1],
  linear: [0, 0, 1, 1],
  'ease-in': [0.42, 0, 1, 1],
  'ease-out': [0, 0, 0.58, 1],
  'ease-in-out': [0.42, 0, 0.58, 1],
};

const scrollTo = ({
  endPos,
  cb,
  animation = 'ease',
  duration = 1000,
  container = document.documentElement,
}) => {
  const step = 1000 / fps;

  let bezierVals = [],
    timeTaken = 0;

  if (typeof animation === 'string') {
    bezierVals =
      predefinedAnimations[
        animation in predefinedAnimations ? animation : 'ease'
      ];
  } else if (Array.isArray(animation)) {
    bezierVals = animation;
  }

  let stepsCompleted = 0,
    easing = BezierEasing(...bezierVals);

  const startPos = container.scrollTop,
    scrollDiff = endPos - startPos;

  while (timeTaken <= duration) {
    (function(timeTaken) {
      window.setTimeout(() => {
        container.scrollTo(
          0,
          startPos + easing(timeTaken / duration) * scrollDiff
        );

        if (timeTaken + step > duration) {
          cb();
        }
      }, step + timeTaken);
    })(timeTaken);

    timeTaken = timeTaken + step;
  }
};

export default scrollTo;

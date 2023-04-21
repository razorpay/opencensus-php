import React from 'react';
import BaseTransition from './BaseTransition';

const direction = {
  // add direction for transition based on use case
  bottom: {
    entering: {
      transform: 'translateY(100vh)',
    },
    entered: {
      transform: 'translateY(0)',
    },
    exiting: {
      transform: 'translateY(100vh)',
    },
    exited: {
      transform: 'translateY(100vh)',
    },
  },
};

const SlideTransition = (props): JSX.Element => {
  const { duration, slideFrom, ...rest } = props;
  const baseStyles = {
    transition: `opacity ${duration}ms ease, transform ${duration}ms ease`,
    willChange: 'opacity, transform',
  };
  return (
    <BaseTransition
      {...rest}
      baseStyles={baseStyles}
      duration={duration}
      transitionStyles={direction[slideFrom]}
    />
  );
};

SlideTransition.defaultProps = {
  duration: 0,
  slideFrom: 'bottom',
};

export { SlideTransition };

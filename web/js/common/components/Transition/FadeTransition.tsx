import React from 'react';
import BaseTransition from './BaseTransition';

const transitionStyles = {
  entering: {
    opacity: 0,
  },
  entered: {
    opacity: 1,
  },
  exiting: {
    opacity: 1,
  },
  exited: {
    opacity: 0,
  },
};

const FadeTransition = (props): JSX.Element => {
  const { duration, ...rest } = props;
  const baseStyles = {
    transition: `opacity ${duration}ms ease-in-out`,
    willChange: 'opacity',
    opacity: 0,
  };
  return (
    <BaseTransition
      {...rest}
      baseStyles={baseStyles}
      duration={duration}
      transitionStyles={transitionStyles}
    />
  );
};

FadeTransition.defaultProps = {
  duration: 0,
};

export { FadeTransition };

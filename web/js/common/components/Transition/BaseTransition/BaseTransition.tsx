import React from 'react';
import { Transition } from 'react-transition-group';

const BaseTransition = (props): JSX.Element => {
  const {
    baseClassName = '',
    baseStyles,
    children,
    duration,
    in: inProp,
    timeout,
    transitionStyles,
    unmount,
    ...rest
  } = props;
  return (
    <Transition in={inProp} timeout={{ enter: timeout, exit: duration }} {...rest}>
      {(status) => {
        if (unmount && status === 'exited') {
          return null;
        }
        return React.cloneElement(children, {
          ...children.props,
          className: `${children.props.className} ${baseClassName}`,
          style: {
            ...baseStyles,
            ...(transitionStyles[status] || {}),
          },
        });
      }}
    </Transition>
  );
};

BaseTransition.defaultProps = {
  duration: 0,
  in: false,
  timeout: 0,
  transitionStyles: {},
  unmount: true,
};

export default BaseTransition;

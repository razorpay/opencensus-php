import React from 'react';
import { useLocation } from 'react-router-dom';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import { TransitionGroup, CSSTransition } from 'react-transition-group';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import { getScreenIndex, SCREEN_TRANSITION_TIME_IN_MS } from '../../../screens/screenHelpers';
import useLocationQuery from '../../../shared/useLocationQuery';

const AnimationView = styled(View)`
  overflow: hidden;
  position: relative;
`;

const AnimationWrapper = ({ children }) => {
  return (
    <Size height="100%">
      <AnimationView>{children}</AnimationView>
    </Size>
  );
};

const AnimationScreen = ({ children, disableTransition }) => {
  const location = useLocation();
  const locationQuery = useLocationQuery();
  const currentScreenIndex = getScreenIndex(locationQuery.get('screen'));

  const previousScreenIndex = location.state?.previousScreenIndex || 0;

  let animationClassNames;

  if (disableTransition) {
    animationClassNames = 'no-animation';
  } else {
    animationClassNames =
      currentScreenIndex > previousScreenIndex ? 'slide-forward' : 'slide-backward';
  }

  return (
    <TransitionGroup
      component={AnimationWrapper}
      childFactory={(child) => React.cloneElement(child, { classNames: animationClassNames })}
    >
      <CSSTransition
        key={location.key}
        classNames={animationClassNames}
        timeout={SCREEN_TRANSITION_TIME_IN_MS}
      >
        {children}
      </CSSTransition>
    </TransitionGroup>
  );
};

AnimationScreen.propTypes = {
  children: PropTypes.node,
  disableTransition: PropTypes.bool,
};

AnimationWrapper.propTypes = {
  children: PropTypes.node,
};

export default AnimationScreen;

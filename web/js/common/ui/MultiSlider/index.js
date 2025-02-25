import React, { Component, useEffect } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { emptySliderStack } from 'merchant_common/reducers/multiSlider';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import { MultiSliderOverlay, multiSliderAttributes } from './MultiSliderStyle';
import MultiSliderComponent from './MultiSlider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';
import { compose } from 'redux';

class MultiSlider extends Component {
  disableScrolling = () => {
    document.body.style.height = '100%';
    document.body.style.overflow = 'hidden';
  };

  enableScrolling = () => {
    document.body.style.height = 'auto';
    document.body.style.overflow = 'auto';
  };

  handlePageClick = (event) => {
    const target = event.target;
    const powerselectMenu = 'body > .tether-element > .PowerSelect__Menu';
    const notification = 'body .layout > .Notifications';
    const calendarPicker = 'body .rc-calendar-picker';
    const multiSlider = '.MultiSlider';
    const sliderElements = [powerselectMenu, notification, calendarPicker, multiSlider].join();
    if (target.closest(sliderElements)) {
      return;
    }

    this.props.emptySliderStack();
  };

  handleEnter = () => {
    this.disableScrolling();
    document.addEventListener('click', this.handlePageClick);
  };

  handleExit = () => {
    this.enableScrolling();
    document.removeEventListener('click', this.handlePageClick);
  };

  render() {
    const { sliderStack } = this.props;
    return (
      <>
        <CSSTransition
          in={sliderStack.length > 0}
          timeout={300}
          classNames="MultiSlider__Overlay--transition"
          unmountOnExit
          onEntered={this.handleEnter}
          onExit={this.handleExit}
        >
          <MultiSliderOverlay />
        </CSSTransition>
        <TransitionGroup component={null}>
          {sliderStack.map(
            ({ id, component, size, transitionSpeed, position, onClose, classString }) => {
              const { width, transitionDuration } = multiSliderAttributes({
                size,
                transitionSpeed,
              });
              return (
                <CSSTransition
                  timeout={transitionDuration}
                  classNames="MultiSlider__Component--transition"
                  key={id}
                >
                  <MultiSliderComponent
                    width={width}
                    transitionDuration={transitionDuration}
                    onClose={onClose}
                    position={position}
                    classString={classString}
                  >
                    <ErrorBoundary resetOnProps>{component}</ErrorBoundary>
                  </MultiSliderComponent>
                </CSSTransition>
              );
            },
          )}
        </TransitionGroup>
      </>
    );
  }
}

const MultiSliderFallback = connect(null, { showNotification: showNotificationProp })(
  ({ showNotification }) => {
    useEffect(() => {
      showNotification({
        type: 'error',
        message: 'Error in opening multi slider',
      });
    }, []);

    return null;
  },
);

const MultiSliderMain = compose(
  withRouter,
  connect((state) => state.multiSlider, { emptySliderStack }),
)(MultiSlider);

const MultiSliderWrapper = () => {
  return (
    <ErrorBoundary resetOnProps FallbackComponent={MultiSliderFallback}>
      <MultiSliderMain />
    </ErrorBoundary>
  );
};

export default MultiSliderWrapper;

import React, { Component } from 'react';

import Popover, { PopoverTitle, PopoverBody } from 'common/ui/Popover';
import { isChildSameType, checkChildrenType } from 'common/utils/react-utils';

export class TourStepTitle extends Component {
  render() {
    return (
      <div className="rzp-tour-step-title">
        <div className="rzp-tour-step-content">{this.props.children}</div>
        {this.props.onClose && (
          <div className="rzp-tour-step-close">
            <i className="i i-close" />
          </div>
        )}
      </div>
    );
  }
}

export class TourStepBody extends Component {
  render() {
    return <div className="rzp-tour-step-body">{this.props.children}</div>;
  }
}

const DotsRenderer = ({ index, totalSteps }) => {
  return (
    <div className="steps-switcher">
      {(function dotsRenderer(dots) {
        if (dots.length === totalSteps) {
          return dots;
        }

        const numDots = dots.length;

        return dotsRenderer(
          dots.concat(
            <div
              className={`step-switch${numDots <= index ? ' active' : ''}`}
              key={numDots}
            />
          )
        );
      })([])}
    </div>
  );
};

const TourStepControls = ({ index, totalSteps, onStepChange, onFinish }) => {
  const isFirstStep = index === 0,
    isLastStep = index + 1 === totalSteps;

  return (
    <div className="rzp-tour-step-controls">
      {!isFirstStep &&
        !isLastStep && (
          <div
            className="prev-step pagination-link"
            onClick={() => onStepChange(index - 1)}
          >
            &lt; Prev
          </div>
        )}

      {totalSteps > 1 &&
        !isLastStep && <DotsRenderer index={index} totalSteps={totalSteps} />}

      <div
        className="next-step pagination-link"
        onClick={() => (isLastStep ? onFinish : onStepChange)(index + 1)}
      >
        <span>{isLastStep ? 'Okay, Got it!' : 'Next >'}</span>
      </div>
    </div>
  );
};

export default class TourStep extends Component {
  render() {
    let tourStepTitle = null,
      tourStepBody = null;

    React.Children.toArray(this.props.children).every(child => {
      if (!tourStepTitle && isChildSameType(child, TourStepTitle)) {
        tourStepTitle = child;
      }

      if (!tourStepBody && isChildSameType(child, TourStepBody)) {
        tourStepBody = child;
      }

      return !tourStepBody || !tourStepBody;
    });

    if (!tourStepTitle && !tourStepBody) {
      return null;
    }

    const {
      index,
      totalSteps,
      onStepChange,
      onStepClose,
      onFinish,
      align,
      to,
      ...otherProps
    } = this.props;

    return (
      <div className="tour-lens-content">
        <Popover persistent={true} align={align} {...otherProps}>
          <PopoverTitle>
            <div className="tourstep-title clearfix">
              <div className="pull-left">
                {tourStepTitle && (
                  <tourStepTitle.type
                    {...tourStepTitle.props}
                    onStepClose={onStepClose}
                  >
                    {tourStepTitle.props.children}
                  </tourStepTitle.type>
                )}
              </div>
              {index + 1 !== totalSteps && (
                <div
                  className="tourstep-close pull-right"
                  onClick={onStepClose}
                >
                  &times;
                </div>
              )}
            </div>
          </PopoverTitle>

          <PopoverBody>
            {tourStepBody && (
              <tourStepBody.type {...tourStepBody.props}>
                {tourStepBody.props.children}
              </tourStepBody.type>
            )}

            <TourStepControls
              index={index}
              totalSteps={totalSteps}
              onStepChange={onStepChange}
              onFinish={onFinish}
            />
          </PopoverBody>
        </Popover>
      </div>
    );
  }
}

TourStep.defaultProps = {};

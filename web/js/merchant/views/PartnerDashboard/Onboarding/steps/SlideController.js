import React from 'react';
import { SliderDots } from 'common/new-ui/Slider';
import { AsyncBtn } from 'common/new-ui/Button';

const SlideController = ({
  sliderProps,
  onNext,
  disNext = false,
  nextBtnLabel,
  nextBtnPendingLabel,
}) => {
  const { next, prev, active } = sliderProps;
  const nextLabel = nextBtnLabel ? nextBtnLabel : 'Next';

  return (
    <div className="slide-controller-wrapper">
      <div className="slide-controller">
        <div>
          <SliderDots {...sliderProps} />
        </div>
        <div style={{ textAlign: 'end' }} className="slide-nav-btns">
          {Boolean(active) && (
            <a onClick={() => prev && prev()} className="btn btn-link slider-btn back">
              Back
            </a>
          )}

          <AsyncBtn.Primary
            className="btn btn-primary slider-btn next"
            disabled={disNext}
            onClick={() => {
              !disNext && next && next();
              return onNext && onNext();
            }}
            pendingState={nextBtnPendingLabel}
          >
            {nextLabel}
            <i className="i i-arrow-forward" />
          </AsyncBtn.Primary>
        </div>
      </div>
    </div>
  );
};

export default SlideController;

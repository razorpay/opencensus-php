import React from 'react';
import { SliderDots } from 'component/Slider';
import { classList } from 'common/util';

const SlideController = ({
  sliderProps,
  onNext,
  disNext = false,
  disBack = false,
  nextBtnLabel,
}) => {
  const { next, prev, active } = sliderProps;
  let nextLabel = Boolean(nextBtnLabel) ? nextBtnLabel : 'Next';
  return (
    <div className="slide-controller-wrapper">
      <div className="slide-controller">
        <div>
          <SliderDots {...sliderProps} />
        </div>
        <div style={{ textAlign: 'end' }} className="slide-nav-btns">
          {Boolean(active) && (
            <a
              onClick={() => prev && prev()}
              className="btn btn-link slider-btn back"
            >
              Back
            </a>
          )}

          <a
            onClick={() => {
              !disNext && next && next();
              onNext && onNext();
            }}
            class={classList(
              'btn btn-primary slider-btn next',
              disNext && 'disabled'
            )}
          >
            {nextLabel}
            <i class="i i-arrow-forward" />
          </a>
        </div>
      </div>
    </div>
  );
};

export default SlideController;

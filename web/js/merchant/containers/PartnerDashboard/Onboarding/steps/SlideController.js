import React from 'react';
import { SliderDots } from 'component/Slider';
import { classList } from 'common/util';

const SlideController = ({
  sliderProps,
  onNext,
  disNext = false,
  disBack = false,
}) => {
  const { next, prev } = sliderProps;
  return (
    <div style={{ width: '50%' }}>
      <div className="slide-controller">
        <div>
          <SliderDots {...sliderProps} />
        </div>
        <div style={{ textAlign: 'end' }}>
          <a onClick={prev} className="btn btn-link">
            Back
          </a>
          <a
            onClick={() => {
              !disNext && next && next();
              onNext && onNext();
            }}
            class={classList(
              'btn btn-primary slider-btn',
              disNext && 'disabled'
            )}
          >
            Next
            <i class="i i-arrow-forward" />
          </a>
        </div>
      </div>
    </div>
  );
};

export default SlideController;

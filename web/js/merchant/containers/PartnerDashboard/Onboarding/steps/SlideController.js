import React from 'react';
import { SliderDots } from 'component/Slider';
const SlideController = ({ sliderProps, onNext }) => {
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
              next && next();
              onNext && onNext();
            }}
            className="btn btn-primary slider-btn"
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

import React from 'react';
import { SliderDots } from 'component/Slider';
import Button from 'component/Button';

const SlideController = ({ sliderProps }) => {
  const { next } = sliderProps;
  return (
    <div className="slide-controller">
      <div>
        <SliderDots {...sliderProps} />
      </div>
      <div>
        <Button onClick={next}> Next</Button>
      </div>
    </div>
  );
};

export default SlideController;

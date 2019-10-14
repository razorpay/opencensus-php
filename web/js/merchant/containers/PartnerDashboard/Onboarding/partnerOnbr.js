import React from 'react';
import Slider, { SliderDots } from 'component/Slider';
import S1 from './steps/S1';
import S2 from './steps/S2';
import S3 from './steps/S3';
import SlideContoller from './steps/SlideController';

function BaseScreen(props) {
  return (
    <div className="partner-onboarding-base-screen">
      <Slider>
        {sliderProps => <S1 key={1} />}
        {sliderProps => <S2 key={2} />}
        {sliderProps => <S3 key={3} />}
        {sliderProps => <SlideContoller key={4} sliderProps={sliderProps} />}
      </Slider>
    </div>
  );
}

export default BaseScreen;

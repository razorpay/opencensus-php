import React from 'react';

import Button from 'common/new-ui/Button';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import { BRANDS_IMG_URL } from 'merchant/views/Navigator/components/OnBoarding/constants';
import {
  CLICK_BACK_ON_BRANDS,
  CLICK_NEXT_ON_BRANDS,
} from 'merchant/views/Navigator/components/OnBoarding/track';

const Brands = ({ sliderProps }) => {
  const handleBackButton = () => {
    sliderProps?.prev();
    trackOptimizerEvents(CLICK_BACK_ON_BRANDS);
  };
  const handleNext = () => {
    sliderProps?.next();
    trackOptimizerEvents(CLICK_NEXT_ON_BRANDS);
  };
  return (
    <div className="OnBoarding--Slide OnBoarding--Features" key="brands">
      <div className="Header">
        <div className="Header-title">Trusted by India's top online brands</div>
      </div>
      <div className="brands">
        <img src={BRANDS_IMG_URL} alt="brands" />
      </div>
      <div className="Button-Container">
        <Button.Transparent iconBefore="arrow-back" type="button" onClick={handleBackButton}>
          Back
        </Button.Transparent>
        <Button className="Forward-Button" iconAfter="arrow-forward" onClick={handleNext}>
          Next
        </Button>
      </div>
    </div>
  );
};

export default Brands;

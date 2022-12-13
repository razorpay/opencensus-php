import React from 'react';

import Button from 'common/new-ui/Button';

import Features from 'merchant/components/OnBoarding/Slides/Features';
import { RZPFeatures } from 'merchant/helpers/data';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import {
  CLICK_ACTIVATE_NOW_ON_FEATURES,
  CLICK_BACK_ON_FEATURES,
} from 'merchant/views/Navigator/components/OnBoarding/track';
import {
  FEATURES_DATA,
  FEATURES_LINKS,
} from 'merchant/views/Navigator/components/OnBoarding/constants';

function Highlights({ sliderProps }) {
  const handleActivateNow = () => {
    sliderProps?.next();

    trackOptimizerEvents(CLICK_ACTIVATE_NOW_ON_FEATURES);
  };

  const handleBackButton = () => {
    sliderProps?.prev();

    trackOptimizerEvents(CLICK_BACK_ON_FEATURES);
  };

  return (
    <Features
      {...sliderProps}
      title="What makes Optimizer smart?"
      nextBtn={
        <Button className="Forward-Button" onClick={handleActivateNow}>
          Activate Now
        </Button>
      }
      feature={RZPFeatures.OPTIMIZER}
      featureLinks={FEATURES_LINKS}
      features={FEATURES_DATA}
      handleBackButton={handleBackButton}
    />
  );
}

export default Highlights;

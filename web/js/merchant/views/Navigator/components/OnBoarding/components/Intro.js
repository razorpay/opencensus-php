import React from 'react';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import { IMG_URL, POINTS } from 'merchant/views/Navigator/components/OnBoarding/constants';
import { CLICK_READ_MORE } from 'merchant/views/Navigator/components/OnBoarding/track';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

function Intro({ sliderProps }) {
  const handleNextSlide = (params) => {
    sliderProps?.next(params);

    trackOptimizerEvents(CLICK_READ_MORE);
  };

  return (
    <Landing
      {...sliderProps}
      className="intro"
      next={handleNextSlide}
      ctaText="Know More"
      title="Optimizer | India’s First AI-Powered Payments Router"
      feature={RZPFeatures.OPTIMIZER}
      imageUrl={IMG_URL}
      desc={renderDescription}
    />
  );
}

function renderDescription() {
  return (
    <>
      <p>Automatically route payments across multiple gateways.</p>
      <ul className="points-wrapper">
        {POINTS.map(({ title }) => (
          <li key={title} className="point">
            <i className="i i-done ModeIndicator--live-icon" />
            <span>{title}</span>
          </li>
        ))}
      </ul>
      <p>No coding required. No tech effort.</p>
    </>
  );
}

export default Intro;

import React from 'react';

// UI
import { Button } from '@razorpay/blade/components';
import FeatureCard from 'merchant/components/Feature';
import {
  StyledButtonContainer,
  StyledFeaturesContainer,
  StyledOnboardingSlide,
} from 'merchant/views/MagicKonnect/styled';

//constants
import { features, ONBOARDING_MAGIC_KONNECT_USER } from 'merchant/views/MagicKonnect/constants';

const SecondSlide = ({ navigateBack, openOnboardingForm, secondaryCta, isExistingUser }) => {
  return (
    <StyledOnboardingSlide className="OnBoarding--Features konnect-onboarding-features">
      <div className="Header">
        <div className="Header-title">{ONBOARDING_MAGIC_KONNECT_USER.SLIDE_TWO.title}</div>
        <div className="Header-external-links" />
      </div>
      <StyledFeaturesContainer className="Features konnect-features">
        {features.map((data, idx) => (
          <FeatureCard {...data} key={idx} />
        ))}
      </StyledFeaturesContainer>
      <StyledButtonContainer className="Button-Container">
        <button
          className="Button--transparent Button"
          onClick={navigateBack}
          data-testid="back-cta"
        >
          <i className="Button-icon Button-icon--before i-arrow-back" />
          Back
        </button>
        {!isExistingUser && (
          <Button onClick={openOnboardingForm}>{secondaryCta || 'Get Started'}</Button>
        )}
      </StyledButtonContainer>
    </StyledOnboardingSlide>
  );
};

export default SecondSlide;

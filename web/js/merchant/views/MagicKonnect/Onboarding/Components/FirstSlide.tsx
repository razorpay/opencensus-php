import React from 'react';

// UI
import ReadMoreCta from './ReadMoreCta';
import { Button } from '@razorpay/blade/components';

// styled components
import {
  StyledButtonContainer,
  StyledOnboardingImg,
  StyledOnboardingSlide,
} from 'merchant/views/MagicKonnect/styled';

// constants
import {
  getMagicKonnectSlideDetails,
  MAGIC_KONNECT_BANNER,
} from 'merchant/views/MagicKonnect/constants';

const FirstSlide = ({ openOnboardingForm, primaryCta, setSlideState, businessName }) => {
  return (
    <StyledOnboardingSlide className="OnBoarding--ImageSlide OnBoarding--Landing OnBoarding--Slide">
      <StyledOnboardingImg className="Landing--Image">
        <img src={MAGIC_KONNECT_BANNER} className="onboarding-img" alt="landing-image" />
      </StyledOnboardingImg>
      <div className="Product--Details">
        <div className="Details-heading">
          <span className="dash" /> {getMagicKonnectSlideDetails(businessName).SLIDE_ONE.product}
        </div>
        <div className="Details-title">
          <div className="Details-title">
            {getMagicKonnectSlideDetails(businessName).SLIDE_ONE.title}
          </div>
        </div>
        <div className="Details-desc">
          {getMagicKonnectSlideDetails(businessName).SLIDE_ONE.description}
        </div>
        <StyledButtonContainer className="Button-Container">
          <Button type="button" variant="primary" marginRight="5px" onClick={openOnboardingForm}>
            {primaryCta || 'Get Started'}
          </Button>
          <ReadMoreCta setSlideState={setSlideState} />
        </StyledButtonContainer>
      </div>
    </StyledOnboardingSlide>
  );
};

export default FirstSlide;

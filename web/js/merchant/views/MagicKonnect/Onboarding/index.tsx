import React, { useState } from 'react';

// UI
import { SliderDots } from 'common/new-ui/Slider';
import Spinner from 'common/ui/Spinner';
import FirstSlide from './Components/FirstSlide';
import SecondSlide from './Components/SecondSlide';

// styles
import { StyledSlider } from 'merchant/views/MagicKonnect/styled';

const MagicKonnectOnboarding = ({
  onClickNextCtaAction,
  isLoading,
  primaryCta = '',
  secondaryCta = '',
  isExistingUser,
}) => {
  const [slideState, setSlideState] = useState({
    activeSlide: 'about',
    activeSlideNo: 0,
  });

  const navigateBack = () => {
    setSlideState({
      activeSlide: 'about',
      activeSlideNo: 0,
    });
  };

  const handleSliderDotClick = (index) => {
    const slideName = index === 1 ? 'banner-card' : 'about';
    setSlideState({
      activeSlide: slideName,
      activeSlideNo: index,
    });
  };

  return (
    <div className="main-container">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner center="center" />
        </div>
      ) : (
        <>
          <div className="OnBoarding OnBoarding--Route">
            <StyledSlider className="Slider">
              {slideState.activeSlide === 'about' ? (
                <FirstSlide
                  openOnboardingForm={onClickNextCtaAction}
                  primaryCta={primaryCta}
                  setSlideState={setSlideState}
                />
              ) : (
                <SecondSlide
                  navigateBack={navigateBack}
                  openOnboardingForm={onClickNextCtaAction}
                  secondaryCta={secondaryCta}
                  isExistingUser={isExistingUser}
                />
              )}
            </StyledSlider>
            <SliderDots
              active={slideState.activeSlideNo}
              goTo={handleSliderDotClick}
              totalSlidesNo={2}
            />
          </div>
        </>
      )}
    </div>
  );
};

export default MagicKonnectOnboarding;

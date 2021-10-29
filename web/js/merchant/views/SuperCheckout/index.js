import React, { useEffect } from 'react';
import { OnBoardingWrapper, FeatureEnableSliderButton } from 'merchant/components/OnBoarding';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import Slider, { SliderDots } from 'common/new-ui/Slider';
import { fetchSuperCheckoutStatus } from 'merchant/reducers/superCheckout';
import { FEATURES_DATA } from 'merchant/views/SuperCheckout/data';
import SuperCheckoutLanding from 'merchant/views/SuperCheckout/components/Landing';
import SuperCheckoutFeatures from 'merchant/views/SuperCheckout/components/Features';
import { AsyncBtn } from 'common/new-ui/Button';
import JoinWaitlistButton from 'merchant/views/SuperCheckout/components/JoinWaitlistButton';

const SuperCheckout = ({ active, user, superCheckout, fetchStatus }) => {
  useEffect(() => {
    fetchStatus();
  }, []);

  const getNextBtnProp = (merchantId, state, sliderProps) => (
    <JoinWaitlistButton merchantId={merchantId} superCheckout={state}>
      {(onClick, buttonText) => (
        <FeatureEnableSliderButton
          isLocalEnabler
          feature={RZPFeatures.SuperCheckout}
          onClick={onClick}
          page={sliderProps.active}
          sliderProps={sliderProps}
          buttonText={buttonText}
        />
      )}
    </JoinWaitlistButton>
  );

  const calloutElement = (merchantId, state) => (
    <JoinWaitlistButton merchantId={merchantId} superCheckout={state}>
      {(onClick, buttonText, isPending) => (
        <div class="Button-Container callout-button">
          <AsyncBtn.Secondary onClick={onClick} isPending={isPending}>
            {buttonText}
          </AsyncBtn.Secondary>
        </div>
      )}
    </JoinWaitlistButton>
  );

  return (
    <OnBoardingWrapper class="SuperCheckout">
      <Slider active={active} afterSlide={getOnBoardingSliderDots()}>
        {(sliderProps) => (
          <SuperCheckoutLanding
            callout={calloutElement(user.current, superCheckout)}
            {...sliderProps}
          />
        )}

        {(sliderProps) => (
          <SuperCheckoutFeatures
            {...sliderProps}
            title="What makes Super Checkout great?"
            nextBtn={getNextBtnProp(user.current, superCheckout, sliderProps)}
            feature={RZPFeatures.SuperCheckout}
            featureLinks={[]}
            features={FEATURES_DATA}
          />
        )}
      </Slider>
    </OnBoardingWrapper>
  );
};

function getOnBoardingSliderDots() {
  return (sliderProps) => <SliderDots {...sliderProps} />;
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  superCheckout: state.superCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  fetchStatus: () => dispatch(fetchSuperCheckoutStatus()),
});

export default connect(mapStateToProps, mapDispatchToProps)(SuperCheckout);

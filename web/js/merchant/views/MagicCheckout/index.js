import React, { useEffect } from 'react';
import { OnBoardingWrapper, FeatureEnableSliderButton } from 'merchant/components/OnBoarding';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import Slider, { SliderDots } from 'common/new-ui/Slider';
import {
  fetchMagicCheckoutStatus,
  fetchIntelligenceConfig,
  resetIntelligenceConfig,
} from 'merchant/reducers/magicCheckout';
import { FEATURES_DATA } from 'merchant/views/MagicCheckout/data';
import MagicCheckoutLanding from 'merchant/views/MagicCheckout/components/Landing';
import MagicCheckoutFeatures from 'merchant/views/MagicCheckout/components/Features';
import { AsyncBtn } from 'common/new-ui/Button';
import TabsContainer from 'merchant/views/MagicCheckout/components/TabsContainer';
import JoinWaitlistButton from 'merchant/views/MagicCheckout/components/JoinWaitlistButton';
import { isMagicCheckoutTabsEnabled } from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import 'merchant/views/MagicCheckout/css/magic_checkout.styl';

const MagicCheckout = ({
  active,
  user,
  fetchStatus,
  magicCheckout,
  fetchIntelligenceConfig,
  resetIntelligenceConfig,
}) => {
  useEffect(() => {
    fetchStatus();
    fetchIntelligenceConfig();

    return () => resetIntelligenceConfig();
  }, []);

  const getNextBtnProp = (sliderProps) => {
    return (
      <JoinWaitlistButton>
        {(onClick, buttonText) => (
          <FeatureEnableSliderButton
            isLocalEnabler
            feature={RZPFeatures.MagicCheckout}
            onClick={onClick}
            page={sliderProps.active}
            sliderProps={sliderProps}
            buttonText={buttonText}
          />
        )}
      </JoinWaitlistButton>
    );
  };

  const calloutElement = () => {
    return (
      <JoinWaitlistButton>
        {(onClick, buttonText, isPending) => (
          <div class="Button-Container callout-button">
            <AsyncBtn.Secondary onClick={onClick} isPending={isPending}>
              {buttonText}
            </AsyncBtn.Secondary>
          </div>
        )}
      </JoinWaitlistButton>
    );
  };

  if (user.isMagicCheckoutLive && isMagicCheckoutTabsEnabled(user)) {
    return (
      <TabsContainer
        user={user}
        isCODIntelligenceEnabled={magicCheckout?.cod_intelligence}
        isCODOrderControlEnabled={magicCheckout?.cod_order_control}
        isPrepayCODEnabled={magicCheckout?.one_cc_prepay_cod_conversion}
        platform={magicCheckout?.platform}
        isRcodEnabled={magicCheckout?.rcod}
        dashboardView={magicCheckout?.dashboard_view}
      />
    );
  }

  return (
    <OnBoardingWrapper class="MagicCheckout">
      <Slider active={active} afterSlide={getOnBoardingSliderDots()}>
        {(sliderProps) => <MagicCheckoutLanding callout={calloutElement()} {...sliderProps} />}

        {(sliderProps) => (
          <MagicCheckoutFeatures
            {...sliderProps}
            title="What makes Magic Checkout great?"
            nextBtn={getNextBtnProp(sliderProps)}
            feature={RZPFeatures.MagicCheckout}
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
  magicCheckout: state.magicCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  fetchStatus: () => dispatch(fetchMagicCheckoutStatus()),
  fetchIntelligenceConfig: () => dispatch(fetchIntelligenceConfig()),
  resetIntelligenceConfig: () => dispatch(resetIntelligenceConfig()),
});

export default connect(mapStateToProps, mapDispatchToProps)(MagicCheckout);

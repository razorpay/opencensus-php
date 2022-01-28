import React, { useEffect } from 'react';
import { OnBoardingWrapper, FeatureEnableSliderButton } from 'merchant/components/OnBoarding';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import Slider, { SliderDots } from 'common/new-ui/Slider';
import { fetchMagicCheckoutStatus } from 'merchant/reducers/magicCheckout';
import { FEATURES_DATA } from 'merchant/views/MagicCheckout/data';
import MagicCheckoutLanding from 'merchant/views/MagicCheckout/components/Landing';
import MagicCheckoutFeatures from 'merchant/views/MagicCheckout/components/Features';
import { AsyncBtn } from 'common/new-ui/Button';
import { NavLink, Route } from 'react-router-dom';
import MagicCheckoutRoutes from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import JoinWaitlistButton from 'merchant/views/MagicCheckout/components/JoinWaitlistButton';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import 'merchant/views/MagicCheckout/css/magic_checkout.styl';

const MagicCheckout = ({ active, user, magicCheckout, fetchStatus }) => {
  useEffect(() => {
    fetchStatus();
  }, []);

  const getNextBtnProp = (merchantId, state, sliderProps) => (
    <JoinWaitlistButton merchantId={merchantId} magicCheckout={state}>
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

  const calloutElement = (merchantId, state) => (
    <JoinWaitlistButton merchantId={merchantId} magicCheckout={state}>
      {(onClick, buttonText, isPending) => (
        <div class="Button-Container callout-button">
          <AsyncBtn.Secondary onClick={onClick} isPending={isPending}>
            {buttonText}
          </AsyncBtn.Secondary>
        </div>
      )}
    </JoinWaitlistButton>
  );

  if (user.isMerchantOnMagicCheckout) {
    return (
      <tabbed-container>
        <header id="super-checkout-header" className="scrollable-tab-header">
          {MagicCheckoutRoutes.map((item) => (
            <ShowWhen
              key={item.path}
              additionalCondition={(_user) => {
                if (!item.condition) return true;
                return item.condition(_user);
              }}
            >
              <NavLink to={item.path} exact>
                {item.tabName}
              </NavLink>
            </ShowWhen>
          ))}
        </header>
        <content>
          <div className="content-wrapper">
            {MagicCheckoutRoutes.map((item) => {
              if (item.condition) {
                return (
                  <ShowWhenRoute
                    path={item.path}
                    key={item.path}
                    exact
                    component={item.Component}
                    additionalCondition={(_user) => item.condition(_user)}
                  />
                );
              }
              return <Route key={item.path} path={item.path} exact component={item.Component} />;
            })}
          </div>
        </content>
      </tabbed-container>
    );
  }

  return (
    <OnBoardingWrapper class="MagicCheckout">
      <Slider active={active} afterSlide={getOnBoardingSliderDots()}>
        {(sliderProps) => (
          <MagicCheckoutLanding
            callout={calloutElement(user.current, magicCheckout)}
            {...sliderProps}
          />
        )}

        {(sliderProps) => (
          <MagicCheckoutFeatures
            {...sliderProps}
            title="What makes Magic Checkout great?"
            nextBtn={getNextBtnProp(user.current, magicCheckout, sliderProps)}
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
});

export default connect(mapStateToProps, mapDispatchToProps)(MagicCheckout);

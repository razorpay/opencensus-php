import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import Slider, { SliderDots } from 'common/new-ui/Slider';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import onBoardingHOC, {
  OnBoardingWrapper,
  getOnBoardingDataFromLocalState,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import { RZPFeatures } from 'merchant/helpers/data';
import lazy from 'merchant/routes/LazyLoader';
import { onboardPageVisit } from 'merchant/views/Navigator/components/OnBoarding/track';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

const Intro = lazy(() =>
  import(
    /* webpackChunkName: 'Intro' */ 'merchant/views/Navigator/components/OnBoarding/components/Intro'
  ),
);

const Highlights = lazy(() =>
  import(
    /* webpackChunkName: 'Highlights' */ 'merchant/views/Navigator/components/OnBoarding/components/Highlights'
  ),
);

const Brands = lazy(() =>
  import(
    /* webpackChunkName: 'Brands' */ 'merchant/views/Navigator/components/OnBoarding/components/Brands'
  ),
);

const Survey = lazy(() =>
  import(
    /* webpackChunkName: 'Survey' */ 'merchant/views/Navigator/components/OnBoarding/components/Survey'
  ),
);

function OptimizerOnBoarding() {
  const {
    lastVisitedScreen = 0,
    haveMultipleGateways = '',
    selectedGateWays = [],
    hasRequestedForActivation,
  } = getOnBoardingDataFromLocalState(RZPFeatures.OPTIMIZER);

  const [helpDetails, setHelpDetails] = useState({
    haveMultipleGateways: haveMultipleGateways || '',
    selectedGateWays: selectedGateWays || [],
  });

  useEffect(() => {
    trackOptimizerEvents(
      onboardPageVisit({
        active: lastVisitedScreen,
        haveMultipleGateways,
        selectedGateWays,
        hasRequestedForActivation,
      }),
    );

    return () =>
      setOnBoardingDataInLocalState({
        feature: RZPFeatures.OPTIMIZER,
        data: { lastVisitedTime: Date.now() },
      });
  }, []);

  useEffect(() => {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.OPTIMIZER,
      data: helpDetails,
    });
  }, [helpDetails]);

  const onSlideChange = (active) => {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.OPTIMIZER,
      data: { lastVisitedScreen: active },
    });

    trackOptimizerEvents(
      onboardPageVisit({
        active,
        haveMultipleGateways,
        selectedGateWays,
        hasRequestedForActivation,
      }),
    );
  };

  const handleHaveMultipleGateways = (e) => {
    setHelpDetails({ ...helpDetails, haveMultipleGateways: e.target.value });
  };

  const handleSelectedGateWays = (e) => {
    const { checked, name } = e.target;
    const gateways = [...helpDetails.selectedGateWays];

    if (checked) {
      gateways.push(name);
    } else {
      const index = gateways.indexOf(name);

      if (index > -1) {
        gateways.splice(index, 1);
      }
    }

    setHelpDetails({ ...helpDetails, selectedGateWays: gateways });
  };

  const isHelpDetailsValid = () => {
    const { haveMultipleGateways, selectedGateWays } = helpDetails;

    if (!haveMultipleGateways || selectedGateWays.length <= 0) {
      return false;
    }

    return true;
  };

  return (
    <OnBoardingWrapper className="Optimizer">
      <SuspenseWithLoader type="center">
        <Slider
          active={lastVisitedScreen || 0}
          afterSlide={(sliderProps) => getOnBoardingSliderDots({ sliderProps, isHelpDetailsValid })}
          onSlideChange={onSlideChange}
        >
          {(sliderProps) => <Intro sliderProps={sliderProps} />}

          {(sliderProps) => <Highlights sliderProps={sliderProps} />}

          {(sliderProps) => <Brands sliderProps={sliderProps} />}

          {(sliderProps) => (
            <Survey
              sliderProps={sliderProps}
              handleHaveMultipleGateways={handleHaveMultipleGateways}
              handleSelectedGateWays={handleSelectedGateWays}
              helpDetails={helpDetails}
              isHelpDetailsValid={isHelpDetailsValid}
            />
          )}
        </Slider>
      </SuspenseWithLoader>
    </OnBoardingWrapper>
  );
}

function getOnBoardingSliderDots({ sliderProps, isHelpDetailsValid }) {
  const { goTo } = sliderProps;

  const updatedGoTo = (idx) => {
    if (idx >= 3) {
      if (isHelpDetailsValid()) {
        goTo(idx);
      } else {
        goTo(-1);
      }
    } else {
      goTo(idx);
    }
  };

  return <SliderDots {...sliderProps} goTo={updatedGoTo} />;
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
  onBoardingHOC({
    feature: RZPFeatures.OPTIMIZER,
  }),
)(OptimizerOnBoarding);

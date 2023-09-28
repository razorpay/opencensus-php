import React from 'react';

import RTracking from 'react-tracking';
import ShowWhen from 'merchant/components/ShowWhen';
import Button from 'common/new-ui/Button';
import { withRouter } from 'common/deprecated/withRouter';
import { compose } from 'redux';
import { connect } from 'react-redux';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { RZPFeatures } from 'merchant/helpers/data';

const WidgetSetup = (props) => {
  const { title, imageUrl, className = '', user } = props;
  const handleBackButtonClick = () => {
    props.history.push(`/affordability/widget/platforms`);
  };

  const handleContinue = () => {
    const affordabilityWidgetProductOnBoarding = {
      ...props.affordabilityWidgetProductOnBoarding,
      showOnboarding: false,
      isTour: false,
      isQuickGuideOpen: false,
    };

    props.handleProductQuickGuide(affordabilityWidgetProductOnBoarding);
    props.history.push(`/affordability/`);
  };

  return (
    <div
      className={`OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing ${className}`}
      key="LandingSlide"
    >
      {imageUrl && (
        <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
          <div className="Landing--Image">
            <img src={imageUrl} alt="landing-image" />
          </div>
        </ShowWhen>
      )}

      <div className="Product--Details">
        <div className="Details-title">{title}</div>
        <div className="callout">{props.children}</div>
      </div>
      <div class="Button-Container">
        <Button.Transparent
          className="Back-Button"
          iconBefore="arrow-back"
          onClick={handleBackButtonClick}
        >
          Back
        </Button.Transparent>

        {user.isAffordabilityWidgetEnabled ? (
          <Button.Primary
            iconAfter="arrow-forward"
            className="Forward-Button"
            onClick={handleContinue}
          >
            Continue
          </Button.Primary>
        ) : null}
      </div>
    </div>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('affordability_onboarding_landing_page')),
  connect(
    (state) => {
      return {
        affordabilityWidget: state.affordabilityWidget,
        user: state.session.user,
        affordabilityWidgetProductOnBoarding: getCurrentProductOnBoardingDetails(
          state,
          RZPFeatures.AFFORDABILITY_WIDGET,
        ),
      };
    },
    {
      handleProductQuickGuide,
    },
  ),
  withRouter,
)(WidgetSetup);

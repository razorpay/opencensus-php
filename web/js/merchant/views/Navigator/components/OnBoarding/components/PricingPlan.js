import React, { useEffect, useState } from 'react';

import ShowWhen from 'merchant/components/ShowWhen';
import {
  getOnBoardingDataFromLocalState,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import { RZPFeatures } from 'merchant/helpers/data';

import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import { IMG_URL } from 'merchant/views/Navigator/components/OnBoarding/constants';
import {
  CLICK_BACK_ON_PRICING_PLAN,
  clickActivateNowOnPricingPlan,
} from 'merchant/views/Navigator/components/OnBoarding/track';

function PricingPlan({ sliderProps }) {
  const local = getOnBoardingDataFromLocalState(RZPFeatures.OPTIMIZER);

  const [hasAgreed, setHasAgreed] = useState(local.hasAgreed ?? false);
  const [hasRequestedForActivation, setHasRequestedForActivation] = useState(
    local.hasRequestedForActivation ?? false,
  );

  useEffect(() => {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.OPTIMIZER,
      data: { hasAgreed, hasRequestedForActivation },
    });
  }, [hasAgreed, hasRequestedForActivation]);

  const handleHasAgreed = (e) => {
    const { checked } = e.target;

    setHasAgreed(checked);
  };

  const handleHasRequestedForActivation = () => {
    setHasRequestedForActivation(!hasRequestedForActivation);

    trackOptimizerEvents(clickActivateNowOnPricingPlan(!hasRequestedForActivation));
  };

  const handleBackButton = () => {
    sliderProps?.prev();

    trackOptimizerEvents(CLICK_BACK_ON_PRICING_PLAN);
  };

  return (
    <div
      className="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing pricing-plan"
      key="Pricing Plan"
    >
      <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
        <div className="Landing--Image">
          <img src={IMG_URL} alt="landing-image" />
        </div>
      </ShowWhen>

      <div className="right-content">
        <div className="pricing-plan-wrapper">
          <div className="header">
            <div className="header-title">Activate Optimizer Now</div>
          </div>

          <div className="description">
            Start setting up rules to route transactions through multiple payment providers and see
            improvements in success rate.
          </div>

          <Input.Check
            defaultValue={hasAgreed}
            className="consent"
            name="hasAgreed"
            fieldLabel="I consent to pay given charges to use Optimizer"
            onChange={handleHasAgreed}
            description={renderDescription}
          />

          <div className="Button-Container">
            <Button.Transparent iconBefore="arrow-back" type="button" onClick={handleBackButton}>
              Back
            </Button.Transparent>

            {hasRequestedForActivation ? (
              <Button className="joined-button" type="button">
                <i className="i i-tick" /> Activation Request Sent
              </Button>
            ) : (
              <Button
                className="Forward-Button"
                type="button"
                onClick={handleHasRequestedForActivation}
                disabled={!hasAgreed}
              >
                Activate Now
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function renderDescription() {
  return (
    <>
      <div className="cost-wrapper">
        <span className="cost">0.25%</span>
        <span className="category">per transaction</span>
      </div>

      <p className="note">
        It will be charged per transaction for internally and externally routed payments. You will
        be charged from your balance at the time of transaction.
      </p>
    </>
  );
}

export default PricingPlan;

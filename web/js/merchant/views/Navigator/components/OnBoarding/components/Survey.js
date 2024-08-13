import React, { useState, useEffect } from 'react';

import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import {
  getOnBoardingDataFromLocalState,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import ShowWhen from 'merchant/components/ShowWhen';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  SURVEY_IMG_URL,
  GATEWAYS_OPTIONS,
  HAVE_MULTIPLE_GATEWAYS_OPTIONS,
} from 'merchant/views/Navigator/components/OnBoarding/constants';
import {
  CLICK_BACK_ON_SURVEY,
  clickBookDemo,
} from 'merchant/views/Navigator/components/OnBoarding/track';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

function Survey({
  sliderProps,
  handleHaveMultipleGateways,
  handleSelectedGateWays,
  helpDetails,
  isHelpDetailsValid,
}) {
  const local = getOnBoardingDataFromLocalState(RZPFeatures.OPTIMIZER);
  const [hasBookedForDemo, setHasBookedForDemo] = useState(local.hasBookedForDemo ?? false);

  useEffect(() => {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.OPTIMIZER,
      data: { hasBookedForDemo },
    });
  }, [hasBookedForDemo]);

  const handleBackButton = () => {
    sliderProps?.prev();

    trackOptimizerEvents(CLICK_BACK_ON_SURVEY);
  };

  const handleHasBookedForDemo = () => {
    setHasBookedForDemo(!hasBookedForDemo);
    trackOptimizerEvents(clickBookDemo(helpDetails));
  };

  return (
    <div
      className="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing survey"
      key="Survey"
    >
      <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
        <div className="Landing--Image">
          <img src={SURVEY_IMG_URL} alt="landing-image" />
        </div>
      </ShowWhen>

      <div className="right-content">
        <div className="survey-wrapper">
          <div className="header">
            <div className="header-title">Please help us with a few details</div>
          </div>

          <Form className="survey-form" onSubmit={handleHasBookedForDemo}>
            <Input.Radio
              className="Input--vTop Input--required"
              size="medium"
              label="Do you already have multiple payment gateways?"
              name="haveMultipleGateways"
              defaultValue={helpDetails.haveMultipleGateways}
              onChange={handleHaveMultipleGateways}
              options={HAVE_MULTIPLE_GATEWAYS_OPTIONS}
            />

            <Input.Group
              className="Input--vTop Input--required"
              label="Which of the below payment gateways do you have/plan to have?"
              size="medium"
            >
              <div className="options">
                {GATEWAYS_OPTIONS.map(({ label, name }) => (
                  <Input.Check
                    defaultValue={helpDetails.selectedGateWays.includes(name)}
                    key={name}
                    name={name}
                    onChange={handleSelectedGateWays}
                    fieldLabel={label}
                  />
                ))}
              </div>
            </Input.Group>

            <div className="Button-Container">
              <Button.Transparent iconBefore="arrow-back" type="button" onClick={handleBackButton}>
                Back
              </Button.Transparent>

              {hasBookedForDemo ? (
                <Button className="joined-button" type="button">
                  <i className="i i-tick" /> Booked a demo
                </Button>
              ) : (
                <Button className="Forward-Button" type="submit" disabled={!isHelpDetailsValid()}>
                  Book a demo
                </Button>
              )}
            </div>
          </Form>
        </div>
      </div>
    </div>
  );
}

export default Survey;

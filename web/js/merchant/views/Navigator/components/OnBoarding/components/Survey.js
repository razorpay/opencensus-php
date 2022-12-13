import React from 'react';

import ShowWhen from 'merchant/components/ShowWhen';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import {
  CLICK_BACK_ON_SURVEY,
  CLICK_NEXT_ON_SURVEY,
} from 'merchant/views/Navigator/components/OnBoarding/track';
import {
  IMG_URL,
  GATEWAYS_OPTIONS,
  HAVE_MULTIPLE_GATEWAYS_OPTIONS,
} from 'merchant/views/Navigator/components/OnBoarding/constants';

function Survey({
  sliderProps,
  handleHaveMultipleGateways,
  handleSelectedGateWays,
  helpDetails,
  isHelpDetailsValid,
}) {
  const handleBackButton = () => {
    sliderProps?.prev();

    trackOptimizerEvents(CLICK_BACK_ON_SURVEY);
  };

  const handleFormSubmit = () => {
    sliderProps?.next();
    trackOptimizerEvents(CLICK_NEXT_ON_SURVEY);
  };

  return (
    <div
      className="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing survey"
      key="Survey"
    >
      <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
        <div className="Landing--Image">
          <img src={IMG_URL} alt="landing-image" />
        </div>
      </ShowWhen>

      <div className="right-content">
        <div className="survey-wrapper">
          <div className="header">
            <div className="header-title">Please help us with a few details</div>
          </div>

          <Form className="survey-form" onSubmit={handleFormSubmit}>
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

              <Button className="Forward-Button" type="submit" disabled={!isHelpDetailsValid()}>
                Next
              </Button>
            </div>
          </Form>

          <div className="quick-tip">
            <b>Quick Tip:</b> You can now go-live on Optimizer with Paytm, PayU and Razorpay with
            just 2-clicks!
          </div>
        </div>
      </div>
    </div>
  );
}

export default Survey;

import React from 'react';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { trackShorterKYCEvents } from 'common/utils/analytics';
import { classList } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { FOOTER_BUTTONS } from 'merchant/views/PartnerDashboard/Activation/utils/ActivationUtils';

import Loader from './Loader';

const Save = ({ saveCurrentTab, sectionName }) => {
  const handleSaveButtonClick = () => {
    saveCurrentTab();
    trackShorterKYCEvents({
      objectName: 'Partner L1 Form Save Button',
      actionName: 'Clicked',
      screen: sectionName,
      properties: {
        sectionName,
        ctaClicked: 'Save',
      },
    });
  };

  return <Button onClick={handleSaveButtonClick}>Save</Button>;
};

const SaveAndNext = ({ next }) => (
  <Button.Primary iconAfter="chevron-right" onClick={next}>
    <span className="device--desktop">Save & Next</span>
    <span className="device--mobile">Next</span>
  </Button.Primary>
);

const SubmitL1Form = ({ canSubmitL1Form, submitL1 }) => (
  <AsyncBtn.Primary
    disabled={!canSubmitL1Form}
    onClick={submitL1}
    pendingState="Verifying"
    name="submit-and-verify"
  >
    {'Submit and Verify'}
  </AsyncBtn.Primary>
);

const SubmitClarifications = ({ canSubmitNeedsClarification, submitClarifications }) => (
  <AsyncBtn.Primary
    disabled={!canSubmitNeedsClarification()}
    onClick={submitClarifications}
    pendingState="Submitting..."
    name="Submit Clarifications"
  >
    Submit Clarifications
  </AsyncBtn.Primary>
);

const Footer = ({
  isSaving,
  defaultMsg,
  submitL1,
  next,
  canSubmitL1Form,
  footerButtons,
  saveCurrentTab,
  canSubmitNeedsClarification,
  submitClarifications,
  isConsentTNC,
  setIsConsentTNC,
  activeTab,
  isFormSubmitted,
  tabs,
}) => {
  const buttons = [];

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_CLARIFICATIONS)) {
    buttons.push(
      <SubmitClarifications
        canSubmitNeedsClarification={canSubmitNeedsClarification}
        submitClarifications={submitClarifications}
      />,
    );
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE)) {
    buttons.push(<Save saveCurrentTab={saveCurrentTab} sectionName={tabs[activeTab]} key="0" />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE_AND_NEXT)) {
    buttons.push(<SaveAndNext next={next} key="1" />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_L1_FORM)) {
    // users can only submit the form if consent is checked
    // and consent can only be given if the all the required fields are filled
    buttons.push(<SubmitL1Form canSubmitL1Form={isConsentTNC} submitL1={submitL1} key="2" />);
  }

  return (
    <footer className="reverse-flex">
      <Loader isSaving={isSaving} defsaultMsg={defaultMsg} />
      <div className="footer-content">
        <ShowWhen additionalCondition={() => activeTab == 2 && !isFormSubmitted}>
          <FooterCheckBox
            canSubmitL1Form={canSubmitL1Form}
            checkbox={isConsentTNC}
            setCheckBox={setIsConsentTNC}
          />
        </ShowWhen>
        {buttons}
      </div>
    </footer>
  );
};

const FooterCheckBox = ({ canSubmitL1Form, checkbox, setCheckBox }) => {
  const handleCheckBoxChecked = () => {
    setCheckBox((prevState) => !prevState);
    trackShorterKYCEvents({
      objectName: 'Partner L1 Form TnC',
      actionName: 'Clicked',
      screen: 'Partner L1 Form TnC',
      properties: {
        sectionName: 'Partner L1 Form TnC',
      },
    });
  };

  return (
    <div className="subfooter">
      <Input.Check
        checked={checkbox}
        onChange={handleCheckBoxChecked}
        autoRender={true}
        disabled={!canSubmitL1Form}
        className={classList('footer-checkbox', !canSubmitL1Form ? 'checkbox-cursor' : '')}
      />
      <span>
        I agree to Razorpay{' '}
        <a
          className="text-primary"
          target="_blank"
          rel="noopener noreferrer"
          href="https://razorpay.com/s/terms/partners"
        >
          Terms and Conditions
        </a>
      </span>
    </div>
  );
};

export default Footer;

import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Loader from './Loader';
import { FOOTER_BUTTONS } from '../Constants';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

const Save = ({ saveCurrentTab }) => <Button onClick={saveCurrentTab}>Save</Button>;

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
    pendingState={'Verifying'}
    name="submit-and-verify"
  >
    {'Submit and Verify'}
  </AsyncBtn.Primary>
);

const SubmitKYCForm = ({ isAllTabsValid, tracking, toggleSubmitLayer }) => (
  <Button.Primary
    disabled={!isAllTabsValid()}
    onClick={() => {
      tracking.trackEvent(window.rzpQ.onbr().initiated('kyc.save_documents'));
      toggleSubmitLayer();
      analyticsTrack({
        objectName: 'SignUp',
        actionName: 'Submit L2 CTA Clicked',
        screen: 'home page',
        properties: {
          ...getCommonSegmentProperties(),
        },
      });
    }}
  >
    Submit Form
  </Button.Primary>
);

const SubmitClarifications = ({ canSubmitNeedsClarification, submitClarifications }) => (
  <AsyncBtn.Primary
    disabled={!canSubmitNeedsClarification}
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
  isUnregBiz,
  isAllTabsValid,
  submitL1,
  saveCurrentTab,
  next,
  toggleSubmitLayer,
  tracking,
  submitClarifications,
  canSubmitL1Form,
  canSubmitNeedsClarification,
  footerButtons,
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
    buttons.push(<Save saveCurrentTab={saveCurrentTab} />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE_AND_NEXT)) {
    buttons.push(<SaveAndNext next={next} />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_L1_FORM)) {
    buttons.push(
      <SubmitL1Form
        canSubmitL1Form={canSubmitL1Form}
        submitL1={submitL1}
        isUnregBiz={isUnregBiz}
      />,
    );
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_KYC_FORM)) {
    buttons.push(
      <SubmitKYCForm
        isAllTabsValid={isAllTabsValid}
        toggleSubmitLayer={toggleSubmitLayer}
        tracking={tracking}
      />,
    );
  }

  return (
    <footer>
      <Loader isSaving={isSaving} defaultMsg={defaultMsg} />
      {buttons}
    </footer>
  );
};

export default Footer;

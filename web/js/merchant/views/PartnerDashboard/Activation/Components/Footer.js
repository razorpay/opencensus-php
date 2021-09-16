import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Loader from './Loader';
import { FOOTER_BUTTONS } from '../utils/ActivationUtils';

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
    buttons.push(<Save saveCurrentTab={saveCurrentTab} key="0" />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE_AND_NEXT)) {
    buttons.push(<SaveAndNext next={next} key="1" />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_L1_FORM)) {
    buttons.push(<SubmitL1Form canSubmitL1Form={canSubmitL1Form} submitL1={submitL1} key="2" />);
  }

  return (
    <footer>
      <Loader isSaving={isSaving} defaultMsg={defaultMsg} />
      {buttons}
    </footer>
  );
};

export default Footer;

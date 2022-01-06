import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Loader from './Loader';
import { connect } from 'react-redux';
import { FOOTER_BUTTONS } from '../Constants';
import { classList } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import * as EventActions from 'merchant/reducers/trackEvents';
import Input from 'common/new-ui/Input';

const Save = ({ saveCurrentTab }) => <Button onClick={saveCurrentTab}>Save</Button>;

const SaveAndNext = ({ isActivationFormFullView, next }) => (
  <Button.Primary iconAfter="chevron-right" onClick={next}>
    <span className="device--desktop">
      {isActivationFormFullView ? 'Save & Continue' : 'Save & Next'}
    </span>
    <span className="device--mobile">Next</span>
  </Button.Primary>
);

let SubmitL1Form = ({ canSubmitL1Form, submitL1, tracking, trackEvents }) => (
  <AsyncBtn.Primary
    disabled={!canSubmitL1Form}
    onClick={() => {
      tracking.trackEvent(window.rzpQ.onbr().initiated('act.submit_form'));
      trackEvents({
        objectName: 'SignUp',
        actionName: 'Submit L1 CTA Clicked',
        screen: 'home page',
      });
      return submitL1();
    }}
    pendingState="Verifying"
    name="submit-and-verify"
  >
    {'Submit and Verify'}
  </AsyncBtn.Primary>
);

SubmitL1Form = connect(null, { ...EventActions })(SubmitL1Form);

let SubmitKYCForm = ({ isAllTabsValid, tracking, toggleSubmitLayer, trackEvents }) => (
  <Button.Primary
    disabled={!isAllTabsValid}
    onClick={() => {
      tracking.trackEvent(window.rzpQ.onbr().initiated('kyc.save_documents'));
      tracking.trackEvent(window.rzpQ.onbr().initiated('kyc.submit_form'));
      toggleSubmitLayer();
      trackEvents({
        objectName: 'SignUp',
        actionName: 'Submit L2 CTA Clicked',
        screen: 'home page',
      });
    }}
  >
    Submit Form
  </Button.Primary>
);

SubmitKYCForm = connect(null, { ...EventActions })(SubmitKYCForm);

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

let FooterCheckBox = ({
  activeTab,
  isL1Submitted,
  canSubmitL1Form,
  checkbox,
  setCheckBox,
  onAction,
  fetchData,
  trackEvents,
}) => {
  const fetchMerchantData = (isChecked) => {
    if (isChecked) {
      fetchData().then((res) => {
        if (res?.data) {
          setCheckBox(isChecked);
        }
      });
    } else {
      setCheckBox(isChecked);
    }
    trackEvents({
      objectName: 'sync experiment checkbox',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        checked: isChecked,
      },
    });
  };
  return (
    <ShowWhen
      additionalCondition={(user) =>
        user.isOrgAllowedFunctionality('external_links') &&
        activeTab == 2 &&
        !isL1Submitted &&
        user.isSyncExperimentEnabled &&
        !user.submitted
      }
    >
      <div className="subfooter">
        <Input.Check
          checked={checkbox}
          onChange={({ target }) => fetchMerchantData(target.checked)}
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
            href="https://razorpay.com/terms/"
            onClick={() => {
              onAction && onAction.trackTnCClick();
              trackEvents({
                objectName: 'Checkbox',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Checkbox Label': 'I agree to Razorpay terms and conditions',
                  'Option Selected': 'I agree to Razorpay terms and conditions',
                  'Element Type': 'Form',
                  Mandatory: 'Yes',
                },
              });
            }}
          >
            Terms and Conditions
          </a>
        </span>
      </div>
    </ShowWhen>
  );
};

FooterCheckBox = connect(null, { ...EventActions })(FooterCheckBox);

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
  activeTab,
  isL1Submitted,
  onAction,
  isCheck,
  fetchData,
  onCheckboxChange,
  isActivationFormFullView,
}) => {
  const buttons = [];

  const onChange = (checked) => {
    onCheckboxChange(checked);
  };

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_CLARIFICATIONS)) {
    buttons.push(
      <SubmitClarifications
        canSubmitNeedsClarification={canSubmitNeedsClarification}
        submitClarifications={submitClarifications}
      />,
    );
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE) && !isActivationFormFullView) {
    buttons.push(<Save saveCurrentTab={saveCurrentTab} />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SAVE_AND_NEXT)) {
    buttons.push(<SaveAndNext isActivationFormFullView={isActivationFormFullView} next={next} />);
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_L1_FORM)) {
    buttons.push(
      <SubmitL1Form
        canSubmitL1Form={canSubmitL1Form && isCheck}
        submitL1={submitL1}
        isUnregBiz={isUnregBiz}
        tracking={tracking}
      />,
    );
  }

  if (footerButtons.includes(FOOTER_BUTTONS.SUBMIT_KYC_FORM)) {
    buttons.push(
      <SubmitKYCForm
        isAllTabsValid={isAllTabsValid()}
        toggleSubmitLayer={toggleSubmitLayer}
        tracking={tracking}
      />,
    );
  }

  return (
    <footer className="reverse-flex">
      <Loader isSaving={isSaving} defsaultMsg={defaultMsg} />
      <div className="footer-content">
        <FooterCheckBox
          activeTab={activeTab}
          isL1Submitted={isL1Submitted}
          canSubmitL1Form={canSubmitL1Form}
          checkbox={isCheck}
          setCheckBox={onChange}
          onAction={onAction}
          fetchData={fetchData}
        />
        {buttons}
      </div>
    </footer>
  );
};

export default Footer;

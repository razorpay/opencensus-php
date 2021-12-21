import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import ActivationProgressBar from 'common/ui/ProgressBar';

import { ACTIVATION_URL, CLARIFICATION_THROUGH_EMAIL, TEST_MODE, PERSONALISE_URL } from './data';
import { trackGoToActivation, trackGoToPersonalise, trackSwitchToLive } from './ga';
import SwitchToMode from './SwitchToMode';

const Icon = ({ isActivated, isSubmitted, isRejected, needsClarification }) => {
  let className = '';

  if (!isActivated && !isSubmitted) {
    className = 'activation-form';
  } else if (isActivated) {
    className = 'done';
  } else if (isRejected) {
    className = 'activation-form-rejected';
  } else if (needsClarification) {
    className = 'activation-form-need-clarification';
  } else {
    className = 'activation-form-submitted';
  }

  return <div className={`activation-step-icon ${className}`} />;
};

const SwitchToLive = ({ stepNum, ...props }) => {
  return (
    <SwitchToMode mode="live" onSwitch={() => trackSwitchToLive(stepNum)} {...props}>
      Switch To Live
    </SwitchToMode>
  );
};

/*
 * WrapperElement returns either a link to different tab
 * or just a plain div with info according to different conditions
 */
const WrapperElement = ({
  mode,
  children,
  isActivated,
  isSubmitted,
  isRejected,
  needsClarification,
  hasPersonalised,
  stepNum,
  isActivationFormFullView,
  ...otherProps
}) => {
  /*
   * Show plain div without any link to any tab if
   * 1) Activation form is rejected
   * 2) Submitted, Activated and Mode is test, where we show a link
   *    to "Switch to Live mode" using "Text" component
   * 3) If submitted and Personalized account, there is nothing he
   *    needs to do , so no link is required
   */
  if (
    isRejected ||
    needsClarification ||
    (isSubmitted && ((isActivated && mode === TEST_MODE) || hasPersonalised))
  ) {
    return (
      <div {...otherProps}>
        <div className="media">{children}</div>
      </div>
    );
  }

  /*
   * show link to
   * 1) Activations tab if the user has not submitted his actiavation form
   * 2) Config page if not personalised, where he needs to update logo
   *    and theme color
   */

  let linkTo = null;
  let trackFunction = null;
  const activationFormUrl = isActivationFormFullView ? '/kyc' : ACTIVATION_URL;

  if (!isSubmitted) {
    linkTo = activationFormUrl;
    trackFunction = trackGoToActivation;
  } else if (!hasPersonalised) {
    linkTo = PERSONALISE_URL;
    trackFunction = trackGoToPersonalise;
  }

  return (
    <Link
      to={linkTo}
      onClick={() => {
        return trackFunction && trackFunction(stepNum);
      }}
      {...otherProps}
    >
      <div className="media">
        {children}
        <div className="media-arrow">
          <i className="i i-chevron-right" />
        </div>
      </div>
    </Link>
  );
};

/*
 * Title for Activation step
 */
const Title = ({
  mode,
  isActivated,
  isSubmitted,
  isRejected,
  hasKeyAccess,
  needsClarification,
}) => {
  return (
    <span>
      {isSubmitted ? (
        <span>
          {isActivated ? (
            <span>
              Account Activated
              {mode === 'live' && !hasKeyAccess && (
                <span>
                  {' '}
                  (Limited Access)
                  <small>
                    <i className="i i-info-circle text-fade" />
                    <Popover align="top" followPointer={true} theme="dark">
                      <PopoverBody>
                        You can still use Payment Links and Invoices. Add your Website/App URL to
                        get access to our API’s and other products like Route, Subscriptions etc.
                      </PopoverBody>
                    </Popover>
                  </small>
                </span>
              )}
            </span>
          ) : isRejected ? (
            <span>
              Activation Not Accepted{' '}
              <small>
                <i className="i i-info-circle text-fade" />
                <Popover align="top" followPointer={true} theme="dark">
                  <PopoverBody>
                    We would not be able to support your business as the bank has not approved your
                    activation request. We have sent you an email with more details.
                  </PopoverBody>
                </Popover>
              </small>
            </span>
          ) : needsClarification ? (
            <span>Activation: Need Clarification</span>
          ) : (
            <span>
              {/*
               * If account is not activated or rejected but submitted,
               * we show "Activation Form Submitted" with popover
               */}
              Activation Form Submitted{' '}
              <small>
                <i className="i i-info-circle text-fade" />
                <Popover align="top" followPointer={true} theme="dark">
                  <PopoverBody>
                    Our team will review the form and submitted documents. We will reach out on your
                    contact email for all updates.
                  </PopoverBody>
                </Popover>
              </small>
            </span>
          )}
        </span>
      ) : (
        'Activate your Account'
      )}
    </span>
  );
};

/*
 * Description for Activation step
 */
const Text = ({
  mode,
  merchantId,
  isActivated,
  isSubmitted,
  isRejected,
  hasPersonalised,
  needsClarification,
  clarificationMode,
  activationProgress,
  stepNum,
}) => {
  if (isRejected) {
    return <span>Please check your email for details.</span>;
  }

  if (needsClarification) {
    return (
      <span>
        {clarificationMode === CLARIFICATION_THROUGH_EMAIL
          ? "Please reply to the email we've sent."
          : "We'll contact you over phone."}
      </span>
    );
  }

  return (
    <span>
      {!isActivated && !isSubmitted ? (
        // if he is neither actived nor submitted

        activationProgress == '100' ? (
          'Submit Activation form to accept payments.'
        ) : (
          'Fill Activation form to accept payments.'
        )
      ) : //if he is either activated or submitted or both

      !hasPersonalised ? (
        // if he is either activated or submitted or both but not personalised

        !isActivated ? (
          // if user had not personalized and not activated but submitted

          'Personalise your account.'
        ) : (
          // if has not personalised , but activated

          <span>
            {mode === TEST_MODE ? (
              <span>
                <Link to={PERSONALISE_URL} onClick={() => trackGoToPersonalise(stepNum)}>
                  Personalise
                </Link>
                <span>
                  {' '}
                  or <SwitchToLive merchantId={merchantId} stepNum={stepNum} />
                </span>
              </span>
            ) : (
              <span>Personalise your Account.</span>
            )}
          </span>
        )
      ) : isActivated ? (
        mode === TEST_MODE ? (
          // user has personalized, activated and is in test mode

          <span>
            <SwitchToLive merchantId={merchantId} stepNum={stepNum} />
          </span>
        ) : (
          'You are all set up.'
        )
      ) : (
        // if user has submitted and is under review

        'Our team is reviewing the submission.'
      )}
    </span>
  );
};

const Progress = ({ progress }) => {
  return (
    <div className="activation-progress">
      <ActivationProgressBar type="success" max={100} value={progress} />
    </div>
  );
};

export default class ActivationStep extends Component {
  getStep() {
    const { isActivated, isSubmitted } = this.props.user;

    if (!isSubmitted) {
      return 1;
    }

    if (!isActivated) {
      return 2;
    }

    return 3;
  }

  render() {
    const { mode, user, config } = this.props;

    const {
      activation_progress: progress,
      isActivated,
      isSubmitted,
      isRejected,
      needsClarification,
      business_website: businessWebsite,
      has_key_access: hasKeyAccess,
      clarification_mode: clarificationMode,
      isActivationFormFullView,
    } = user;

    const { hasPersonalised } = config;

    const stepNum = this.getStep();

    return (
      <WrapperElement
        class="Onboarding__Step"
        mode={mode}
        isActivated={isActivated}
        isSubmitted={isSubmitted}
        isRejected={isRejected}
        needsClarification={needsClarification}
        hasPersonalised={hasPersonalised}
        stepNum={stepNum}
        isActivationFormFullView={isActivationFormFullView}
      >
        <div className="media-icon">
          <Icon
            isActivated={isActivated}
            isSubmitted={isSubmitted}
            isRejected={isRejected}
            needsClarification={needsClarification}
            clarificationMode={clarificationMode}
          />
        </div>
        <div class="media-body">
          <div className="activation-progress-cont">
            <div>
              <b>
                <Title
                  mode={mode}
                  isActivated={isActivated}
                  isSubmitted={isSubmitted}
                  hasPersonalised={hasPersonalised}
                  isRejected={isRejected}
                  needsClarification={needsClarification}
                  businessWebsite={businessWebsite}
                  hasKeyAccess={hasKeyAccess}
                />
              </b>
              {!isActivated && !isSubmitted && (
                <span className="activation-progress-num">{progress}%</span>
              )}
            </div>
            {!isActivated && !isSubmitted && (
              <div>
                <Progress progress={progress} />
              </div>
            )}
          </div>
          <div className="step-desc">
            <Text
              merchantId={user.current}
              mode={mode}
              isActivated={isActivated}
              isSubmitted={isSubmitted}
              isRejected={isRejected}
              hasPersonalised={hasPersonalised}
              needsClarification={needsClarification}
              clarificationMode={clarificationMode}
              activationProgress={user.activation_progress}
              stepNum={stepNum}
            />
          </div>
        </div>
      </WrapperElement>
    );
  }
}

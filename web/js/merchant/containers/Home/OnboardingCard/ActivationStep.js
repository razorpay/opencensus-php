import React, { Component } from 'react';
import TetherComponent from 'react-tether';
import { Link } from 'react-router-dom';

import LocalStorageService from 'rzp/utils/localStorage';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import ProgressBar from 'rzp/ui/ProgressBar';

import { activationDuration } from 'common/data';

import {
  NEEDS_CLARIFICATION,
  ACTIVATION_URL,
  CLARIFICATION_THROUGH_CALL,
  CLARIFICATION_THROUGH_EMAIL,
  TEST_MODE,
  LIVE_MODE,
  PERSONALISE_URL,
} from './data';
import {
  trackActivationCardAction,
  trackGoToActivation,
  trackGoToPersonalise,
  trackSwitchToLive,
} from './ga';

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

class SwitchToLive extends Component {
  constructor(props) {
    super(props);

    this.modeToken = `rzp_mode--${props.merchantId}`;

    this.switchToLive = this.switchToLive.bind(this);
  }

  switchToLive() {
    if (!LocalStorageService.getItem(`hide-mode-dd-popover`)) {
      LocalStorageService.setItem(`show-mode-dd-popover`, 'true');
    }

    trackSwitchToLive(this.props.stepNum);
    LocalStorageService.setItem(this.modeToken, LIVE_MODE);
    window.location.reload();
  }

  render() {
    return (
      <a className="switch-to-live" onClick={this.switchToLive}>
        {this.props.children}
      </a>
    );
  }
}

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

  let linkTo = null,
    trackFunction = null;

  if (!isSubmitted) {
    linkTo = ACTIVATION_URL;
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
  children,
  isActivated,
  isSubmitted,
  isRejected,
  needsClarification,
}) => {
  return (
    <span>
      {isSubmitted ? (
        <span>
          {isActivated ? (
            'Account Activated'
          ) : isRejected ? (
            <span>
              Activation Not Accepted{' '}
              <small>
                <i className="i i-info-circle text-fade" />
                <Popover align="top" followPointer={true} theme="dark">
                  <PopoverBody>
                    We would not be able to support your business as the bank
                    has not approved your activation request. We have sent you
                    an email with more details.
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
                    Your account is Under Review. The process usually takes{' '}
                    {activationDuration}. We will reach out on your contact
                    email for further clarifications.
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
  children,
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
                <Link
                  to={PERSONALISE_URL}
                  onClick={() => trackGoToPersonalise(stepNum)}
                >
                  Personalise
                </Link>
                <span>
                  {' '}
                  or{' '}
                  <SwitchToLive merchantId={merchantId} stepNum={stepNum}>
                    Switch to live
                  </SwitchToLive>
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
            <SwitchToLive merchantId={merchantId} stepNum={stepNum}>
              Switch to live
            </SwitchToLive>
          </span>
        ) : (
          'You are all set up.'
        )
      ) : (
        // if user has submitted and is under review

        `It may take ${activationDuration} for review.`
      )}
    </span>
  );
};

const Progress = ({ progress }) => {
  return (
    <div className="activation-progress">
      <ProgressBar type="success" max={100} value={progress} />
    </div>
  );
};

export default class ActivationStep extends Component {
  constructor(props) {
    super(props);
  }

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
      clarification_mode: clarificationMode,
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
                  isActivated={isActivated}
                  isSubmitted={isSubmitted}
                  hasPersonalised={hasPersonalised}
                  isRejected={isRejected}
                  needsClarification={needsClarification}
                />
              </b>
              {!isActivated &&
                !isSubmitted && (
                  <span className="activation-progress-num">{progress}%</span>
                )}
            </div>
            {!isActivated &&
              !isSubmitted && (
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

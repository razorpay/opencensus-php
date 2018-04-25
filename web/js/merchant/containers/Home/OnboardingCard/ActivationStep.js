import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import LocalStorageService from 'rzp/utils/localStorage';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import ProgressBar from 'rzp/ui/ProgressBar';

import {
  NEEDS_CLARIFICATION,
  ACTIVATION_URL,
  CLARIFICATION_THROUGH_CALL,
  CLARIFICATION_THROUGH_EMAIL,
  TEST_MODE,
  LIVE_MODE,
  PERSONALISE_URL,
} from './data';

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
  }

  switchToLive() {
    LocalStorageService.setItem('rzp_mode', LIVE_MODE);
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
  return (
    <Link
      to={
        (!isSubmitted && ACTIVATION_URL) ||
        (!hasPersonalised && PERSONALISE_URL)
      }
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
const Title = ({ children, isActivated, isSubmitted, isRejected }) => {
  return (
    <span>
      {isSubmitted ? (
        <span>
          {isActivated ? (
            'Your Account is Activated'
          ) : isRejected ? (
            <span>Activation Form Rejected</span>
          ) : (
            <span>
              {/*
                    * If account is not activated or rejected but submitted,
                    * we show "Activation Form Submitted" with popover
                    */}
              Activation Form Submitted{' '}
              <small>
                <i className="i i-info-circle text-fade" />
                <Popover align="top" followPointer={true}>
                  <PopoverBody>
                    Your account is Under Review. The process usually takes 2 to
                    3 working days. We will reach out on your contact email for
                    further clarifications.
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
  isActivated,
  isSubmitted,
  isRejected,
  hasPersonalised,
  needsClarification,
  clarificationMode,
}) => {
  if (isRejected) {
    return <span>Unable to address your business use case</span>;
  }

  if (needsClarification) {
    return (
      <span>
        {'Action Required. We will ' +
          (clarificationMode === CLARIFICATION_THROUGH_EMAIL
            ? 'email'
            : 'call') +
          ' you'}
      </span>
    );
  }

  return (
    <span>
      {!isActivated && !isSubmitted ? (
        'Fill activation form to accept payments.'
      ) : !hasPersonalised ? (
        !isActivated ? (
          'Personalise your account'
        ) : (
          <span>
            {mode === TEST_MODE ? (
              <span>
                <Link to={PERSONALISE_URL}>Personalise</Link>
                <span>
                  {' '}
                  or <SwitchToLive>Switch to live</SwitchToLive>
                </span>
              </span>
            ) : (
              <span>Personalise your Account</span>
            )}
          </span>
        )
      ) : isActivated && mode === TEST_MODE ? (
        <span>
          <SwitchToLive>Switch to live</SwitchToLive>
        </span>
      ) : (
        'You are all set up.'
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

export default ({ mode, user, config }) => {
  const {
    activation_progress: progress,
    isActivated,
    isSubmitted,
    isRejected,
    needsClarification,
    clarification_mode: clarificationMode,
  } = user;

  const { hasPersonalised } = config;

  return (
    <WrapperElement
      class="Onboarding__Step"
      mode={mode}
      isActivated={isActivated}
      isSubmitted={isSubmitted}
      isRejected={isRejected}
      needsClarification={needsClarification}
      hasPersonalised={hasPersonalised}
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
            mode={mode}
            isActivated={isActivated}
            isSubmitted={isSubmitted}
            isRejected={isRejected}
            hasPersonalised={hasPersonalised}
            needsClarification={needsClarification}
            clarificationMode={clarificationMode}
          />
        </div>
      </div>
    </WrapperElement>
  );
};

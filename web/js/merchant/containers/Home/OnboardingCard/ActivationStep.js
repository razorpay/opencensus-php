import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import LocalStorageService from 'rzp/utils/localStorage';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import ProgressBar from 'rzp/ui/ProgressBar';

const Icon = ({ isActivated, isSubmitted }) => {
  let className = '';

  if (!isActivated && !isSubmitted) {
    className = 'activation-form';
  } else if (isActivated) {
    className = 'done';
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
    LocalStorageService.setItem('rzp_mode', 'live');
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

const WrapperElement = ({
  mode,
  children,
  isActivated,
  isSubmitted,
  hasPersonalised,
  ...otherProps
}) => {
  if (isSubmitted && (mode === 'test' || hasPersonalised)) {
    return (
      <div {...otherProps}>
        <div className="media">{children}</div>
      </div>
    );
  }

  return (
    <Link
      to={(!isSubmitted && '/activation') || (!hasPersonalised && '/config')}
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

const Title = ({ children, isActivated, isSubmitted }) => {
  return (
    <span>
      {isSubmitted ? (
        <span>
          {isActivated ? (
            'Your Account is Activated'
          ) : (
            <span>
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

const Text = ({
  mode,
  children,
  isActivated,
  isSubmitted,
  hasPersonalised,
}) => {
  return (
    <span>
      {!isActivated && !isSubmitted ? (
        'Fill activation form to accept payments.'
      ) : !hasPersonalised ? (
        !isActivated ? (
          'Personalise your account'
        ) : (
          <span>
            {mode === 'test' ? (
              <span>
                <Link to="/config">Personalise</Link>
                <span>
                  {' '}
                  or <SwitchToLive>Switch to live</SwitchToLive> mode
                </span>
              </span>
            ) : (
              <span>Personalise your Account</span>
            )}
          </span>
        )
      ) : isActivated && mode === 'test' ? (
        <span>
          <SwitchToLive>Switch to live</SwitchToLive> mode
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
  const { activation_progress: progress, isActivated, isSubmitted } = user;

  const { hasPersonalised } = config;

  return (
    <WrapperElement
      class="Onboarding__Step"
      mode={mode}
      isActivated={isActivated}
      isSubmitted={isSubmitted}
      hasPersonalised={hasPersonalised}
    >
      <div className="media-icon">
        <Icon isActivated={isActivated} isSubmitted={isSubmitted} />
      </div>
      <div class="media-body">
        <div className="activation-progress-cont">
          <div>
            <b>
              <Title
                isActivated={isActivated}
                isSubmitted={isSubmitted}
                hasPersonalised={hasPersonalised}
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
            hasPersonalised={hasPersonalised}
          />
        </div>
      </div>
    </WrapperElement>
  );
};

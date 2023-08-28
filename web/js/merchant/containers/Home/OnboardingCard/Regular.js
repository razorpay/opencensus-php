import { Component } from 'react';
import { connect } from 'react-redux';

import ShowWhen from 'merchant/components/ShowWhen';
import Group, { GroupItem } from 'common/ui/Group';

import ActivationStep from './ActivationStep';
import Integration from './Integration';
import { onBoardingItems, LIVE_MODE } from './data';
import { trackWelcomeCTAClick, trackCloseOnboarding } from './ga';
import OnboardingPreview from 'assets/onboarding.svg';

@connect((state) => ({ ...state.session, config: state.config.config }))
export default class OnboardingCard extends Component {
  constructor(props) {
    super(props);

    const { mode, user, isFirstStep } = props;
    const { isActivated, isSubmitted } = user;
    // const { hasPersonalised } = config;

    this.state = {
      integrated: false,
      activated: mode === 'live' && isActivated && isSubmitted,
    };

    if (typeof window.hj === 'function') {
      window.hj('trigger', 'onboarding_card');
      window.hj('tagRecording', [isFirstStep ? 'welcome_step_opened' : 'main_step_opened']);
    }

    this.onIntegrationComplete = this.onIntegrationComplete.bind(this);
    this.closeOnboarding = this.closeOnboarding.bind(this);
  }

  onIntegrationComplete() {
    this.setState({
      integrated: true,
    });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { mode, integration } = nextProps;
    const { keysGenerated, paymentsMade } = integration;

    if (!this.state.integrated && mode === LIVE_MODE && keysGenerated && paymentsMade) {
      this.onIntegrationComplete();
    }
  }

  gotoNextStep = () => {
    trackWelcomeCTAClick();
    return this.props.onFirstStepClose && this.props.onFirstStepClose();
  };

  closeOnboarding(e, fromCloseBtn) {
    trackCloseOnboarding(`from ${fromCloseBtn ? 'close icon' : 'description'}`);
    return this.props.onClose && this.props.onClose();
  }

  render() {
    const { user, config, integration, mode, isFirstStep } = this.props;
    const { integrated, activated } = this.state;

    let FirstStep = null;

    if (isFirstStep) {
      FirstStep = (
        <div class="media-body">
          <div class="media-heading">
            <span className="highlight">W</span>elcome{user.isOrgRZP ? ' to Razorpay!' : '!'}
            Let's get you going.
          </div>
          <div className="onboarding-desc">
            Your {user.isOrgRZP ? 'Razorpay' : 'dashboard'} account is ready to use! There is a lot
            that you can do on the Dashboard. Here are some of the actions that you can take:
          </div>
          <div class="row">
            {onBoardingItems(user).map((item, index) => {
              return (
                <div className="col-md-4" key={index}>
                  <div className="onboarding-checklist-item">
                    <div className="icon-cont">
                      <i className="i i-check" />
                    </div>
                    <div>{item}</div>
                  </div>
                </div>
              );
            })}
          </div>

          <button class="btn btn-default onboarding-cta" onClick={this.gotoNextStep}>
            <span>Okay, got it</span>
            <i class="i i-chevron-right" />
          </button>
        </div>
      );
    } else {
      FirstStep = (
        <div class="media-body">
          <div class="media-heading">
            <span className="highlight">G</span>etting Started{' '}
            {user.isOrgRZP ? 'with Razorpay' : ''}
          </div>
          <div className="onboarding-desc">
            {mode === 'test' ? (
              <span>
                You are currently in test mode. Feel free to explore the dashboard or do the
                following:
              </span>
            ) : (
              <span>
                {integrated && activated ? (
                  <span>
                    You are all set up. You may now <a onClick={this.closeOnboarding}>close this</a>{' '}
                    or view our{' '}
                    <a href="https://razorpay.com/docs" target="_blank" rel="noreferrer noopener">
                      documentation
                    </a>{' '}
                    from top right.
                  </span>
                ) : (
                  <span>
                    You are now in Live Mode. Generate live API keys and Integrate to start
                    accepting payments.
                  </span>
                )}
              </span>
            )}
          </div>
          <div className="onboarding-steps">
            <Group>
              <ShowWhen additionalCondition={(user) => user.isAllowedView('activation')}>
                <GroupItem>
                  <ActivationStep mode={mode} user={user} config={config} />
                </GroupItem>
              </ShowWhen>
              <GroupItem>
                <Integration
                  mode={mode}
                  hasKeyAccess={user.has_key_access}
                  businessWebsite={user.business_website}
                  integration={integration}
                />
              </GroupItem>
            </Group>
          </div>
        </div>
      );
    }

    return (
      <div className="onboarding-card-wrapper">
        <div className={`onboarding-card-wrapper-content${isFirstStep ? ' first-step' : ''}`}>
          <div class="media onboarding-card">
            {FirstStep}
            <div class="onboarding-illustration">
              <img
                src={OnboardingPreview}
                alt="onboarding"
                // eslint-disable-next-line react/no-unknown-property
                fetchpriority="high"
              />
            </div>
            {(isFirstStep || (integrated && activated)) && (
              <a
                onClick={isFirstStep ? this.gotoNextStep : (e) => this.closeOnboarding(e, true)}
                className="close"
              >
                <i className="i i-close" />
              </a>
            )}
          </div>
        </div>
      </div>
    );
  }
}

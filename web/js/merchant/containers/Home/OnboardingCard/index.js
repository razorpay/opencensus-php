import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Group, { GroupItem } from 'rzp/ui/Group';
import LocalStorageService from 'rzp/utils/localStorage';

import newProducts from 'merchant/containers/Banners/newProducts';
import MediaCard from 'merchant/containers/Home/OnboardingCard/MediaCard';

import ActivationStep from './ActivationStep';
import Integration from './Integration';
import { onBoardingItems } from './data';
import {
  trackWelcomeCTAClick,
  trackCloseOnboarding,
  trackGoToDocumentation,
} from './ga';

@connect(state => ({ ...state.session, config: state.config.config }))
export default class OnboardingCard extends Component {
  constructor(props) {
    super(props);

    const { mode, user, config, isFirstStep } = props,
      { isActivated, isSubmitted } = user,
      { hasPersonalised } = config;

    this.state = {
      integrated: false,
      activated: mode === 'live' && isActivated && isSubmitted,
    };

    if (typeof window.hj === 'function') {
      window.hj('trigger', 'onboarding_card');
      window.hj('tagRecording', [
        isFirstStep ? 'welcome_step_opened' : 'main_step_opened',
      ]);
    }

    this.onIntegrationComplete = this.onIntegrationComplete.bind(this);
    this.closeOnboarding = this.closeOnboarding.bind(this);
  }

  onIntegrationComplete() {
    this.setState({
      integrated: true,
    });
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
    let { user, config, payments, mode, isFirstStep } = this.props;
    let { integrated, activated } = this.state;

    let FirstStep = null;

    if (isFirstStep) {
      FirstStep = (
        <div class="media-body">
          <div class="media-heading">
            <span className="highlight">W</span>elcome to Razorpay! Let's get
            you going.
          </div>
          <div className="onboarding-desc">
            Your Razorpay account is ready to use! There is a lot that you can
            do on the Dashboard. Here are some of the actions that you can take:
          </div>
          <div class="row">
            {onBoardingItems.map((item, index) => {
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

          <button
            class="btn btn-default onboarding-cta"
            onClick={this.gotoNextStep}
          >
            <span>Okay, got it</span>
            <i class="i i-chevron-right" />
          </button>
        </div>
      );
    } else {
      FirstStep = (
        <div class="media-body">
          <div class="media-heading">
            <span className="highlight">G</span>etting Started with Razorpay
          </div>
          <div className="onboarding-desc">
            {mode === 'test' ? (
              <span>
                You are currently in test mode. Feel free to explore the
                dashboard or do the following:
              </span>
            ) : (
              <span>
                {integrated && activated ? (
                  <span>
                    You are all set up. You may now{' '}
                    <a onClick={this.closeOnboarding}>close this</a> or view our{' '}
                    <a href="https://docs.razorpay.com/" target="_blank">
                      documentation
                    </a>{' '}
                    from top right.
                  </span>
                ) : (
                  <span>
                    You are now in Live Mode. Generate live API keys and
                    Integrate to start accepting payments.
                  </span>
                )}
              </span>
            )}
          </div>
          <div className="onboarding-steps">
            <Group>
              <GroupItem>
                <ActivationStep mode={mode} user={user} config={config} />
              </GroupItem>
              <GroupItem>
                <Integration
                  mode={mode}
                  payments={payments}
                  onFinish={this.onIntegrationComplete}
                />
              </GroupItem>
            </Group>
          </div>
        </div>
      );
    }

    return (
      <div className="onboarding-card-wrapper">
        <div
          className={`onboarding-card-wrapper-content${
            isFirstStep ? ' first-step' : ''
          }`}
        >
          <div class="media onboarding-card">
            {FirstStep}
            <div class="onboarding-illustration" />
            {(isFirstStep || (integrated && activated)) && (
              <a
                onClick={
                  isFirstStep
                    ? this.gotoNextStep
                    : e => this.closeOnboarding(e, true)
                }
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

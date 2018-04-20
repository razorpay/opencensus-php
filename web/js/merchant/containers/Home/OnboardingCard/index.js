import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Group, { GroupItem } from 'rzp/ui/Group';
import LocalStorageService from 'rzp/utils/localStorage';

import newProducts from 'merchant/containers/Banners/newProducts';
import MediaCard from 'merchant/containers/Home/OnboardingCard/MediaCard';

import ActivationStep from './ActivationStep';
import Integration from './Integration';

const analyticsGoTo = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Home',
    eventAction: `Go To - ${name.replace('Razorpay ', '')}`,
  });
};

const analyticsLearnMore = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Home',
    eventAction: `Learn More - ${name.replace('Razorpay ', '')}`,
  });
};

const onBoardingItems = [
  'Generate Financial Reports',
  'Check Transaction History',
  'Access API keys  & Webhooks',
  'Access Razorpay Products',
  'Check Settlements',
  'Issue Refunds',
];

@connect(state => ({ ...state.session, config: state.config.config }))
export default class OnboardingCard extends Component {
  constructor(props) {
    super(props);

    this.state = {
      integrated: false,
    };

    this.onIntegrationComplete = this.onIntegrationComplete.bind(this);
  }

  componentWillMount() {
    if (JSON.parse(LocalStorageService.getItem('ngStorage-new_user_signup'))) {
      LocalStorageService.setItem('onboarding_first_step', true);
      LocalStorageService.setItem('show_onboarding_card', true);
      LocalStorageService.setItem('ngStorage-new_user_signup', false);
    }

    this.setState({
      showOnboarding: LocalStorageService.getItem('show_onboarding_card'),
      isFirstStep: LocalStorageService.getItem('onboarding_first_step'),
      showNewProductsBanner: !LocalStorageService.getItem(
        'hide_newproducts_banner'
      ),
    });
  }

  onIntegrationComplete() {
    this.setState({
      integrated: true,
    });
  }

  gotoNextStep = () => {
    this.setState(
      { isFirstStep: false },
      () => this.props.onSizeChange && this.props.onSizeChange()
    );
    LocalStorageService.removeItem('onboarding_first_step');
  };

  closeOnboarding = () => {
    this.setState({ showOnboarding: false });
    LocalStorageService.removeItem('show_onboarding_card');
  };

  render() {
    let { user, config, payments, mode } = this.props;
    let { isFirstStep, showOnboarding, integrated } = this.state;

    let FirstStep = null;
    const isOldUser = !JSON.parse(
      LocalStorageService.getItem('ngStorage-new_user_signup')
    );

    if (!showOnboarding) {
      return null;
    }

    if (isFirstStep) {
      FirstStep = (
        <div class="media-body">
          <div class="media-heading">
            <span className="highlight">W</span>elcome to Razorpay! Let's get
            you going.
          </div>
          <div className="onboarding-desc">
            Yayy!! Your Razorpay account is created successfullly. There are a
            lot of possibilities that you can explore with us. Here are some of
            the actions that you can take:
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
            class="btn btn-lg btn-default onboarding-cta"
            onClick={this.gotoNextStep}
          >
            <span>Got it! Let’s Start Exploring</span>
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
                You are now in Live Mode. Generate live API keys and Integrate
                to go live.
              </span>
            )}
          </div>
          <div className="onboarding-steps">
            <Group>
              <GroupItem>
                <ActivationStep mode={mode} user={user} config={config} />
              </GroupItem>
              <GroupItem>
                <Integration mode={mode} payments={payments} />
              </GroupItem>
            </Group>
          </div>
        </div>
      );
    }

    return (
      <div className="onboarding-card-wrapper">
        <div class={`media onboarding-card ${isFirstStep ? 'first-step' : ''}`}>
          {FirstStep}
          <div class="onboarding-illustration" />
        </div>
      </div>
    );
  }
}

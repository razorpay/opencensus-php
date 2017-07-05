import { Component } from 'react';
import { connect } from 'react-redux';
import LocalStorageService from 'rzp/utils/localStorage';
import ActivationStep from './ActivationStep';
import KeyGenerationStep from './KeyGenerationStep';
import PaymentsReceivedStep from './PaymentsReceivedStep';

@connect(state => state.session)
export default class OnboardingCard extends Component {
  state = {};

  componentWillMount() {
    if (LocalStorageService.getItem('ngStorage-new_user_signup')) {
      LocalStorageService.setItem('onboarding_first_step', true);
      LocalStorageService.setItem('show_onboarding_card', true);
      LocalStorageService.removeItem('ngStorage-new_user_signup');
    }

    this.setState({
      showOnboarding: LocalStorageService.getItem('show_onboarding_card'),
      isFirstStep: LocalStorageService.getItem('onboarding_first_step'),
    });
  }

  gotoNextStep = () => {
    this.setState({ isFirstStep: false });
    LocalStorageService.removeItem('onboarding_first_step');
  };

  closeOnboarding = () => {
    this.setState({ showOnboarding: false });
    LocalStorageService.removeItem('show_onboarding_card');
  };

  render() {
    let { user, mode, modeFormatted, payments = [] } = this.props;

    if (!this.state.showOnboarding) {
      return null;
    }

    return (
      <div class="media onboarding-card">
        <div class="media-left">
          <img
            class="media-object"
            src="styles/assets/onboarding-illustration.png"
          />
        </div>

        {this.state.isFirstStep
          ? <div class="media-body">
              <div class="media-heading">
                Welcome to Razorpay. Let's get started.
              </div>
              <p>
                Dashboard is your one stop for all your payments. Using dashboard, here are some of the things you can do:
              </p>
              <ul class="row">
                <li class="col-sm-4">Complete Activation Process</li>
                <li class="col-sm-4">Generate Financial Reports</li>
                <li class="col-sm-4">Issue Refunds</li>
                <li class="col-sm-4">Check Transaction History</li>
                <li class="col-sm-4">Access Razorpay Products</li>
                <li class="col-sm-4">Check Settlements</li>
              </ul>

              <button
                class="btn btn-lg btn-default"
                onClick={this.gotoNextStep}
              >
                <span>Got it! So, what's next?</span>
                <i class="icon icon-chevron-right" />
              </button>
            </div>
          : <div class="media-body">
              {user.isActivated
                ? <button class="close" onClick={this.closeOnboarding}>
                    <i class="icon icon-close" />
                  </button>
                : null}
              <div class="media-heading">Your Next Steps...</div>
              <p>
                Your Razorpay account is created. Now, you can browse through the dashboard or do the following:
              </p>
              <div class="row">
                <div class="col-sm-6" style={{ paddingRight: 0 }}>
                  <ActivationStep user={user} />
                </div>

                <div class="col-sm-6">
                  {payments.length
                    ? <PaymentsReceivedStep />
                    : <KeyGenerationStep
                        user={user}
                        mode={mode}
                        modeFormatted={modeFormatted}
                        isLoading={payments.loading}
                      />}
                </div>
              </div>

              {user.isActivated
                ? <div style={{ marginTop: '12px' }}>
                    You may now
                    {' '}
                    <a onClick={this.closeOnboarding}>close this card</a>
                    . You can access the
                    {' '}
                    <a>documentation</a>
                    {' '}
                    from topbar, if needed.
                  </div>
                : null}
            </div>}

      </div>
    );
  }
}

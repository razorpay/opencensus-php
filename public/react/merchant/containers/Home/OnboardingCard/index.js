import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import LocalStorageService from 'rzp/utils/localStorage';
import ActivationStep from './ActivationStep';
import KeyGenerationStep from './KeyGenerationStep';
import PaymentsReceivedStep from './PaymentsReceivedStep';
import OnboardingIllustrationPNG from 'styles/assets/onboarding-illustration.svg';

import newProducts from 'merchant/containers/Banners/newProducts';
import MediaCard from 'merchant/containers/Home/OnboardingCard/MediaCard';

const NewProducts = () => {
  const productItemStyle = { width: `${100 / newProducts.length}%` };

  return (
    <div className="media-body">
      <div className="media-heading">Explore Our Product Stack</div>
      <p>
        Presenting India’s first holistic converged payment solution for you.
        Check our brand new products.
      </p>
      <div className="new-products-row">
        {newProducts.map((product, key) =>
          <div key={key} className={`product-item`} style={productItemStyle}>
            <MediaCard title={product.name} symbol={product.symbol}>
              <div className="text-small m-b">
                {product.description}
              </div>
              <div className="links">
                <Link to={product.link}>Try Now</Link>
                <span className="text-fade" style={{ padding: '0 4px' }}>
                  &nbsp;•&nbsp;
                </span>
                <a href={product.help} target="_blank">
                  Learn More
                </a>
              </div>
            </MediaCard>
          </div>
        )}
      </div>
    </div>
  );
};

@connect(state => state.session)
export default class OnboardingCard extends Component {
  state = {};

  componentWillMount() {
    if (JSON.parse(LocalStorageService.getItem('ngStorage-new_user_signup'))) {
      LocalStorageService.setItem('onboarding_first_step', true);
      LocalStorageService.setItem('show_onboarding_card', true);
      LocalStorageService.setItem('ngStorage-new_user_signup', false);
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
    let { isFirstStep, showOnboarding } = this.state;

    let FirstStep = null;
    const isOldUser = !JSON.parse(
      LocalStorageService.getItem('ngStorage-new_user_signup')
    );

    if (showOnboarding) {
      if (isFirstStep) {
        FirstStep = (
          <div class="media-body">
            <div class="media-heading">
              Welcome to Razorpay. Let's get started.
            </div>
            <p>
              Dashboard is your one stop for all your payments. Using dashboard,
              here are some of the things you can do:
            </p>
            <ul class="row">
              <li class="col-sm-4">Complete Activation Process</li>
              <li class="col-sm-4">Generate Financial Reports</li>
              <li class="col-sm-4">Issue Refunds</li>
              <li class="col-sm-4">Check Transaction History</li>
              <li class="col-sm-4">Access Razorpay Products</li>
              <li class="col-sm-4">Check Settlements</li>
            </ul>

            <button class="btn btn-lg btn-primary" onClick={this.gotoNextStep}>
              <span>Got it! So, what's next?</span>
              <i class="icon icon-chevron-right" />
            </button>
          </div>
        );
      } else {
        FirstStep = (
          <div class="media-body">
            {user.isActivated
              ? <button class="close" onClick={this.closeOnboarding}>
                  <i class="icon icon-close" />
                </button>
              : null}
            <div class="media-heading">Your Next Steps...</div>
            <p>
              Your Razorpay account is created. Now, you can browse through the
              dashboard or do the following:
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
                    />}
              </div>
            </div>

            {user.isActivated
              ? <div style={{ marginTop: '12px' }}>
                  You may now{' '}
                  <a onClick={this.closeOnboarding}>close this card</a>
                  . You can access the <a>documentation</a> from topbar, if
                  needed.
                </div>
              : null}
          </div>
        );
      }
    }
    return (
      <div>
        {FirstStep &&
          <div
            class={`media onboarding-card ${isFirstStep ? 'first-step' : ''}`}
          >
            <div class="media-left">
              <img class="media-object" src={OnboardingIllustrationPNG} />
            </div>
            {FirstStep}
          </div>}
        {isOldUser &&
          <div class={`media onboarding-card new-features`}>
            <NewProducts />
          </div>}
      </div>
    );
  }
}

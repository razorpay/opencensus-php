import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import LocalStorageService from 'rzp/utils/localStorage';

import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { required } from 'rzp/utils/validators';

import Spinner from 'rzp/ui/Spinner';
import { showNotification } from 'rzp/modules/notifications';

import {
  saveOnboarding,
  getOnboardingResponse,
} from 'merchant/modules/onboarding';

import FORM_TYPE from './Forms';

import './OnBoarding.styl';

@connect(state => state.session, {
  saveOnboarding,
  getOnboardingResponse,
  showNotification,
})
@reduxForm({
  form: 'featureOnboardingForm',
})
export default class OnBoarding extends Component {
  state = {
    submitted: false,
    isLoading: false,
    uploadedFile: null,
  };

  componentWillMount() {
    this.setState({
      isLoading: true,
    });

    this.props
      .getOnboardingResponse(this.props.formType)
      .then(response => {
        const submitted = response.data && !(response.data instanceof Array);

        this.setState({
          isLoading: false,
          submitted: submitted,
        });
      })
      .catch(err => {
        // Handle errors
      });
  }

  componentDidMount() {
    const img = document.querySelector('.feature-image');

    if (img.complete) {
      img.classList.remove('fadein');
      setTimeout(() => img.classList.add('fadein'));
    } else {
      img.addEventListener('load', () => {
        img.classList.add('fadein');
      });
    }
  }

  // For file uploader
  handleChange = event => {
    this.setState({ uploadedFile: event.target.files[0] });
  };

  // Form submit handler
  onSubmitClick = props => {
    const prom = new Promise(() => {
      let data = {};
      let file = null;
      let fileName = null;

      if (this.state.uploadedFile && this.props.formType === 'marketplace') {
        file = this.state.uploadedFile;
        fileName = 'vendor_agreement';
      }

      if (this.props.formType === 'subscriptions' && props.website_checkbox) {
        delete props.website_checkbox;
      }

      return this.props
        .saveOnboarding(this.props.formType, props, file, fileName)
        .then(() => {
          this.props.showNotification({
            type: 'success',
            message: 'Your request has been submitted',
          });
          this.setState({ submitted: true });
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    });

    return prom;
  };

  // For optimization: to download only the required image
  requireImage(formType, currentForm) {
    switch (formType) {
      case 'marketplace':
        currentForm.formImage = require('styles/assets/landing/route.svg');
        break;

      case 'virtual_accounts':
        currentForm.formImage = require('styles/assets/landing/smartcollect.svg');
        break;

      case 'subscriptions':
        currentForm.formImage = require('styles/assets/landing/subscriptions.svg');
        break;
    }
  }

  switchToTestMode = () => {
    LocalStorageService.setItem('rzp_mode', 'test');
    window.location.reload();
  };

  render() {
    const {
      handleSubmit,
      invalid,
      heading,
      description,
      formType,
      enableFeatureInTestMode,
      isTestMode,
    } = this.props;

    const currentForm = FORM_TYPE[formType];
    const WizardForm = currentForm.formComponent;
    this.requireImage(formType, currentForm);

    return (
      <div class="onboarding-page-container">
        <div
          class={`onboarding-container clearfix ${isTestMode
            ? 'halfForm'
            : 'fullForm'}`}
        >
          {/* Feature Description */}
          <main class="onboarding-overview col-md-7 col-xs-12">
            <h2>
              {heading}
            </h2>
            <p>
              {description}
            </p>

            <div class="action-container">
              {/* Test mode only button to enable feature in test mode */}
              {isTestMode &&
                <AsyncButton
                  type="button"
                  class="btn btn-primary"
                  text="Get Started"
                  pendingText="Enabling..."
                  onClick={enableFeatureInTestMode}
                />}

              <div class="action-links">
                <a
                  class="btn-link"
                  target="_blank"
                  href={currentForm.links.knowMore}
                >
                  Know more
                </a>
                <span class="dot" />
                <a
                  class="btn-link"
                  target="_blank"
                  href={currentForm.links.docs}
                >
                  View Docs
                </a>
              </div>
            </div>

            {/* halfForm hides image on small screen. Live mode will have 'halfForm' class if form is not submitted */}
            <div
              class={`banner-figure clearfix ${!isTestMode &&
              !this.state.submitted
                ? 'halfForm'
                : ''}`}
            >
              <img
                class={`feature-image ${formType}`}
                src={currentForm.formImage}
              />
            </div>
          </main>

          {/* Feature Form for live mode*/}
          {!isTestMode &&
            <aside class="onboarding-form col-md-5 col-xs-12">
              <h3>Get Started</h3>
              <p>
                {currentForm.formText}
              </p>

              {this.state.isLoading
                ? <div class="page-spinner-container">
                    <Spinner />
                  </div>
                : !this.state.submitted
                  ? <div>
                      <WizardForm handleChange={this.handleChange} />
                      <AsyncButton
                        type="button"
                        class="btn btn-primary pull-left"
                        style={{ marginTop: '20px' }}
                        text="Apply Now"
                        pendingText="Applying..."
                        onClick={handleSubmit(this.onSubmitClick)}
                        disabled={
                          invalid ||
                          (formType === 'marketplace' &&
                            !this.state.uploadedFile)
                        }
                      />
                    </div>
                  : <div class="alert alert-info">
                      Your form is submitted for enabling {heading}. Meanwhile,
                      you can switch to{' '}
                      <a onClick={this.switchToTestMode}>Test Mode</a> to try
                      the product.
                      {!this.props.user.isActivated
                        ? <div class="m-t">
                            <b> Please note </b> that this is activation form
                            for {heading}. Your request will be processed after
                            you submit the{' '}
                            <Link to="/activation">
                              primary activation form
                            </Link>.
                          </div>
                        : <div class="m-t">
                            <b> Please note </b> that your request will be
                            processed in 1 working day.
                          </div>}
                    </div>}
            </aside>}
        </div>
      </div>
    );
  }
}

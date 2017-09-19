import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import { RouteForm } from './Forms';

const FORM_TYPE = {
  routes: {
    formComponent: RouteForm,
    imagePath: 'styles/assets/route-landing.svg',
    links: {
      docs: '',
      knowMore: '',
    },
    formText: '',
  },
};

@connect(state => state.session)
@reduxForm({
  form: 'onboardingForm',
})
export default class TestModeBanner extends Component {
  save = () => {};

  render() {
    const { heading, description, links, formType, handleSubmit } = this.props;

    const CURRENT_FORM = FORM_TYPE[formType];
    CURRENT_FORM.link.docs += 'https://razorpay.com/docs/';
    const formImage = require(CURRENT_FORM.imagePath);

    let testMode = true;

    <div class="onboarding-container">
      {/* Feature Description */}
      <div class="onboarding-overview">
        <h1>
          {heading}
        </h1>
        <p>
          {description}
        </p>

        <div class="action-container">
          {testMode &&
            <AsyncButton
              type="button"
              class="btn btn-primary pull-left"
              text="Enabling in Test Mode"
              pendingText="Enabling..."
              onClick={handleSubmit(this.save)}
            />}

          <a class="btn-link" href={CURRENT_FORM.link.knowMore}>
            Know More
          </a>
          <span class="dot" />
          <a class="btn-link" href={CURRENT_FORM.link.docs}>
            View Docs
          </a>
        </div>

        <img class="feature-image" src={formImage} />
      </div>

      {/* Feature Form */}
      <div class="onboarding-form">
        <h2>Get Started</h2>
        <p>
          {CURRENT_FORM.formText}
        </p>
        <CURRENT_FORM.formComponent />

        <AsyncButton
          type="button"
          class="btn btn-primary pull-left"
          text="Apply Now"
          pendingText="Applying..."
          onClick={handleSubmit(this.save)}
        />
      </div>
    </div>;
  }
}

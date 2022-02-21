import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { titleCase } from 'common/utils/rzp-utils';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import ShowWhen from 'merchant/components/ShowWhen';

import { trackGoToKeyGen, trackGoToDocumentation, trackGoToPayments } from './ga';

const Icon = ({ mode, keysGenerated, paymentsMade, hasKeyAccess, businessWebsite }) => {
  let className = '';

  if (mode === 'live' && !hasKeyAccess) {
    if (businessWebsite) {
      className = 'activation-form-need-clarification';
    } else {
      className = 'keygen';
    }
  } else if (!keysGenerated) {
    className = 'keygen';
  } else if (!paymentsMade) {
    className = 'integrate';
  } else {
    className = 'browse';
  }

  return <div className={`activation-step-icon ${className}`} />;
};

const Arrow = () => (
  <div className="media-arrow">
    <i className="i i-chevron-right" />
  </div>
);

class WrapperElement extends Component {
  constructor(props) {
    super(props);

    this.trackStep = this.trackStep.bind(this);
  }

  trackStep() {
    const { stepNum, mode } = this.props;

    return (stepNum === 2
      ? trackGoToDocumentation
      : stepNum === 1
      ? trackGoToKeyGen
      : trackGoToPayments)(stepNum, mode);
  }

  render() {
    const {
      mode,
      children,
      keysGenerated,
      paymentsMade,
      stepNum,
      hasKeyAccess,
      businessWebsite,
      ...otherProps
    } = this.props;

    if (mode === 'live' && !hasKeyAccess && businessWebsite) {
      return (
        <div {...otherProps}>
          <div className="media">{children}</div>
        </div>
      );
    }

    if (keysGenerated && !paymentsMade) {
      const content = (
        <div className="media">
          {children}
          <Arrow />
        </div>
      );
      return (
        <React.Fragment>
          <ShowWhen
            additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
          >
            <a
              href="https://razorpay.com/docs/payment-gateway/getting-started-guide/"
              target="_blank"
              rel="noreferrer noopener"
              onClick={this.trackStep}
              {...otherProps}
            >
              {content}
            </a>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}
          >
            {content}
          </ShowWhen>
        </React.Fragment>
      );
    }

    return (
      <Link
        to={(((mode === 'live' && !hasKeyAccess) || !keysGenerated) && '/keys') || '/payments'}
        onClick={this.trackStep}
        {...otherProps}
      >
        <div className="media">
          {children}
          <Arrow />
        </div>
      </Link>
    );
  }
}

const Title = ({ mode, keysGenerated, paymentsMade, hasKeyAccess }) => {
  const formattedMode = titleCase(mode);

  let text = '';

  if (mode === 'live' && !hasKeyAccess) {
    text = 'Get Complete Account Access';
  } else if (!keysGenerated) {
    text = `Integrate in ${formattedMode} Mode`;
  } else if (!paymentsMade) {
    text = `Integrate & Create ${formattedMode} Payment`;
  } else {
    text = `You Received a ${formattedMode} Payment`;
  }

  return <span>{text}</span>;
};

const Text = ({ mode, keysGenerated, hasKeyAccess, businessWebsite, paymentsMade }) => {
  let text = '';

  if (mode === 'live' && !hasKeyAccess) {
    if (businessWebsite) {
      text = 'Your Website/App details are under review.';
    } else {
      text = 'Add Business Website/App details.';
    }
  } else if (!keysGenerated) {
    text = `Generate ${mode} API keys.`;
  } else if (!paymentsMade) {
    text = 'Go through our Documentation.';
  } else {
    text = 'View all payments in Transactions tab.';
  }

  return <span>{text}</span>;
};

export default class IntegrationStep extends Component {
  // eslint-disable-next-line no-useless-constructor
  constructor(props) {
    super(props);
  }

  getStep() {
    const { keysGenerated, paymentsMade } = this.props;

    if (!keysGenerated) {
      return 1;
    }

    if (!paymentsMade) {
      return 2;
    }

    return 3;
  }

  render() {
    // eslint-disable-next-line one-var
    const { mode, hasKeyAccess, businessWebsite, integration } = this.props,
      { isLoading, keysGenerated, paymentsMade } = integration,
      // eslint-disable-next-line no-unused-vars
      isIntegrated = keysGenerated && paymentsMade,
      stepNum = this.getStep();

    const commonProps = { mode, hasKeyAccess, businessWebsite };

    return (
      <WrapperElement
        {...commonProps}
        keysGenerated={keysGenerated}
        paymentsMade={paymentsMade}
        stepNum={stepNum}
        className={`Onboarding__Step ${isLoading ? ' loading' : ''}`}
      >
        <div className="media-icon">
          <Icon {...commonProps} {...integration} />
        </div>
        <div className="media-body">
          <b>
            <Title {...commonProps} {...integration} />
            {isLoading && <PlaceholderLoader />}
          </b>
          <div className="step-desc">
            <Text {...commonProps} {...integration} />
            {isLoading && <PlaceholderLoader />}
          </div>
        </div>
      </WrapperElement>
    );
  }
}

import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchKeys } from 'merchant/modules/keys';

import { titleCase } from 'rzp/utils/rzp-utils';
import LocalStorageService from 'rzp/utils/localStorage';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

import { LIVE_MODE } from './data';
import {
  trackGoToKeyGen,
  trackGoToDocumentation,
  trackGoToPayments,
} from './ga';

const Icon = ({
  mode,
  keysGenerated,
  paymentsMade,
  hasKeyAccess,
  businessWebsite,
}) => {
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
      : stepNum === 1 ? trackGoToKeyGen : trackGoToPayments)(stepNum, mode);
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
      return (
        <a
          href="https://docs.razorpay.com/docs/getting-started"
          target="_blank"
          onClick={this.trackStep}
          {...otherProps}
        >
          <div className="media">
            {children}
            <Arrow />
          </div>
        </a>
      );
    }

    return (
      <Link
        to={
          (((mode === 'live' && !hasKeyAccess) || !keysGenerated) && '/keys') ||
          '/payments'
        }
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

const Title = ({
  mode,
  children,
  keysGenerated,
  paymentsMade,
  hasKeyAccess,
  businessWebsite,
  ...otherProps
}) => {
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

const Text = ({
  mode,
  children,
  keysGenerated,
  hasKeyAccess,
  businessWebsite,
  paymentsMade,
  ...otherProps
}) => {
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

@connect(null, { fetchKeys })
export default class IntegrationStep extends Component {
  constructor(props) {
    super(props);

    const { mode, payments } = props;

    this.state = {
      isLoading: true,
      keysGenerated: false,
      paymentsMade: false,
    };

    this.paymentsRequest = new Promise((res, rej) => {
      this.onFetchPayments = res;

      if (!props.payments.loading) {
        res(props.payments.items);
      }
    });
  }

  getStep() {
    const { keysGenerated, paymentsMade } = this.state;

    if (!keysGenerated) {
      return 1;
    }

    if (!paymentsMade) {
      return 2;
    }

    return 3;
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.payments.loading && !nextProps.payments.loading) {
      this.onFetchPayments(nextProps.payments.items);
    }
  }

  componentWillMount() {
    let params = {};

    params.mode = this.props.mode;

    Promise.all([
      this.props.fetchKeys(params).then(({ data }) => {
        return !!data.items.length;
      }),
      this.paymentsRequest.then(payments => {
        return !!payments.length;
      }),
    ]).then(resp => {
      const { 0: keysGenerated, 1: paymentsMade } = resp;

      this.setState(
        {
          isLoading: false,
          keysGenerated,
          paymentsMade,
        },
        () => {
          const { keysGenerated, paymentsMade } = this.state;

          if (this.props.mode === LIVE_MODE && keysGenerated && paymentsMade) {
            this.props.onFinish();
          }
        }
      );
    });
  }

  render() {
    const { mode, hasKeyAccess, businessWebsite } = this.props,
      { isLoading, keysGenerated, paymentsMade } = this.state,
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
          <Icon {...commonProps} {...this.state} />
        </div>
        <div className="media-body">
          <b>
            <Title {...commonProps} {...this.state} />
            {isLoading && <PlaceholderLoader />}
          </b>
          <div className="step-desc">
            <Text {...commonProps} {...this.state} />
            {isLoading && <PlaceholderLoader />}
          </div>
        </div>
      </WrapperElement>
    );
  }
}

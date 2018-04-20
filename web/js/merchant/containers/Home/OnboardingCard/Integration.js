import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchKeys } from 'merchant/modules/keys';

import { titleCase } from 'rzp/utils/rzp-utils';
import LocalStorageService from 'rzp/utils/localStorage';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

const Icon = ({ mode, keysGenerated, paymentsMade, visitedTransactions }) => {
  let className = '';

  if (!keysGenerated) {
    className = 'keygen';
  } else if (!paymentsMade) {
    className = 'integrate';
  } else {
    if (!visitedTransactions || mode !== 'live') {
      className = 'browse';
    } else {
      className = 'done';
    }
  }

  return <div className={`activation-step-icon ${className}`} />;
};

const Arrow = () => (
  <div className="media-arrow">
    <i className="i i-chevron-right" />
  </div>
);

const WrapperElement = ({
  children,
  keysGenerated,
  paymentsMade,
  visitedTransactions,
  ...otherProps
}) => {
  if (keysGenerated && paymentsMade && visitedTransactions) {
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
    <Link to={paymentsMade ? '/payments' : '/keys'} {...otherProps}>
      <div className="media">
        {children}
        <Arrow />
      </div>
    </Link>
  );
};

const Title = ({
  mode,
  children,
  keysGenerated,
  paymentsMade,
  visitedTransactions,
  ...otherProps
}) => {
  const formattedMode = titleCase(mode);

  let text = '';

  if (mode === 'live' && keysGenerated && paymentsMade && visitedTransactions) {
    text = `Integrated in ${formattedMode} Mode`;
  } else {
    if (!keysGenerated) {
      text = `Integrate Razorpay in ${formattedMode} Mode`;
    } else if (!paymentsMade) {
      text = `Integrate & Create ${formattedMode} Payment`;
    } else {
      text = `You Received a ${formattedMode} Payment`;
    }
  }

  return <span>{text}</span>;
};

const Text = ({
  mode,
  children,
  keysGenerated,
  paymentsMade,
  visitedTransactions,
  ...otherProps
}) => {
  const formattedMode = titleCase(mode);

  let text = '';

  if (keysGenerated && paymentsMade && visitedTransactions) {
    if (mode !== 'live') {
      text = 'You can view all payments in Transaction tab';
    } else {
      text = 'You are all set up.';
    }
  } else {
    if (!keysGenerated) {
      text = `Generate ${mode} API keys.`;
    } else if (!paymentsMade) {
      text = 'Go through our Documentation';
    } else {
      text = 'Go to transactions tab to view all payments';
    }
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
      visitedTransactions:
        (mode === 'test' &&
          LocalStorageService.getItem('visited_test_transactions')) ||
        (mode === 'live' &&
          LocalStorageService.getItem('visited_live_transactions')),
    };

    this.paymentsRequest = new Promise((res, rej) => {
      this.onFetchPayments = res;
    });
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

      this.setState({
        isLoading: false,
        keysGenerated,
        paymentsMade,
      });
    });
  }

  render() {
    const { mode } = this.props,
      {
        isLoading,
        keysGenerated,
        paymentsMade,
        visitedTransactions,
      } = this.state,
      isIntegrated = keysGenerated && paymentsMade;

    return (
      <WrapperElement
        keysGenerated={keysGenerated}
        paymentsMade={paymentsMade}
        visitedTransactions={visitedTransactions}
        className={`Onboarding__Step ${isLoading ? ' loading' : ''}`}
      >
        <div className="media-icon">
          <Icon mode={mode} {...this.state} />
        </div>
        <div className="media-body">
          <b>
            <Title mode={mode} {...this.state} />
            {isLoading && <PlaceholderLoader />}
          </b>
          <div className="step-desc">
            <Text mode={mode} {...this.state} />
            {isLoading && <PlaceholderLoader />}
          </div>
        </div>
      </WrapperElement>
    );
  }
}

import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchKeys } from 'merchant/modules/keys';

import { titleCase } from 'rzp/utils/rzp-utils';
import LocalStorageService from 'rzp/utils/localStorage';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

const Icon = ({ mode, keysGenerated, paymentsMade }) => {
  let className = '';

  if (!keysGenerated) {
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

const WrapperElement = ({
  children,
  keysGenerated,
  paymentsMade,
  ...otherProps
}) => {
  if (keysGenerated && paymentsMade) {
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
    <Link to="/keys" {...otherProps}>
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
  ...otherProps
}) => {
  const formattedMode = titleCase(mode);

  let text = '';

  if (!keysGenerated) {
    text = `Integrate Razorpay in ${formattedMode} Mode`;
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
  paymentsMade,
  ...otherProps
}) => {
  let text = '';

  if (!keysGenerated) {
    text = `Generate ${mode} API keys.`;
  } else if (!paymentsMade) {
    text = 'Go through our Documentation';
  } else {
    text = 'You can view all payments in Transaction tab';
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

      //res([{"id":"pay_A2YobW8CYKWnvd","entity":"payment","amount":50000,"currency":"INR","status":"authorized","order_id":null,"invoice_id":null,"international":false,"method":"netbanking","amount_refunded":0,"amount_transferred":0,"refund_status":null,"captured":false,"description":"Add Funds to Account","card_id":null,"bank":"SBIN","wallet":null,"vpa":null,"email":"prashanth.pamidi+28@razorpay.com","contact":"+911122334456","notes":{"dashboard":"true"},"fee":null,"tax":null,"error_code":null,"error_description":null,"created_at":1524464555}]);
      if (!props.payments.loading) {
        res(props.payments.items);
      }
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

      this.setState(
        {
          isLoading: false,
          keysGenerated,
          paymentsMade,
        },
        () => {
          const { keysGenerated, paymentsMade } = this.state;

          if (this.props.mode === 'live' && keysGenerated && paymentsMade) {
            this.props.onFinish();
          }
        }
      );
    });
  }

  render() {
    const { mode } = this.props,
      { isLoading, keysGenerated, paymentsMade } = this.state,
      isIntegrated = keysGenerated && paymentsMade;

    return (
      <WrapperElement
        keysGenerated={keysGenerated}
        paymentsMade={paymentsMade}
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

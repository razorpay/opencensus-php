import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchToMode from 'merchant/containers/Home/OnboardingCard/SwitchToMode';
// eslint-disable-next-line import/no-named-as-default
import Step, { StepTitle, StepContent, possibleStatuses } from './Step';
import { showProductsModal } from 'merchant/reducers/home';
import rTracking from 'react-tracking';
import { compose } from 'redux';

const TestProducts = ({ onClick }) => (
  <span className="btn-link cursor-pointer" onClick={onClick}>
    Test Products
  </span>
);

const initialState = {
  title: 'Test Mode Enabled',
  status: possibleStatuses.done,
  content: null,
};

class TestMode extends Component {
  constructor(props) {
    super(props);
    this.state = initialState;
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { mode, integration, merchantId, track } = nextProps;
    const { isLoading, keysGenerated, paymentsMade } = integration;

    let { title, status, content } = initialState;

    if (mode === 'live') {
      content = (
        <span>
          You can try out the Dashboard in{' '}
          <SwitchToMode mode="test" merchantId={merchantId}>
            <span onClick={() => track.switchToTest()}>Test Mode</span>
          </SwitchToMode>
        </span>
      );
    } else if (isLoading) {
      status = possibleStatuses.loading;
    } else {
      // eslint-disable-next-line no-lonely-if
      if (paymentsMade) {
        title = 'Test Mode Payments';
        content = (
          <span>
            View all payments received in Test mode in{' '}
            <Link to="/payments" onClick={() => track.viewTransactions()}>
              Transactions
            </Link>{' '}
            tab
          </span>
        );
      } else if (!keysGenerated) {
        content = (
          <span>
            <Link to="/keys" className="btn-link" onClick={() => track.generateTestKeys()}>
              Generate Test Keys
            </Link>{' '}
            and use{' '}
            <TestProducts
              // eslint-disable-next-line no-sequences
              onClick={() => (track.viewTestProducts(), this.props.showProductsModal())}
            />{' '}
            to find the right fit for your use-case
          </span>
        );
      } else {
        title = 'Transact in Test Mode';
        content = (
          <span>
            Create Test payments now. For details, Read{' '}
            <ShowWhen
              additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
            >
              <a
                target="_blank"
                rel="noreferrer noopener"
                className="btn-link"
                href="https://razorpay.com/docs"
              >
                documentation
              </a>{' '}
            </ShowWhen>
            <ShowWhen
              additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}
            >
              documentation{' '}
            </ShowWhen>
            or use{' '}
            <TestProducts
              // eslint-disable-next-line no-sequences
              onClick={() => (track.viewTestProducts(), this.props.showProductsModal())}
            />
          </span>
        );
      }
    }

    if (this.onActive && status !== this.state.status && status === possibleStatuses.active) {
      this.onActive();
    }

    this.setState({
      title,
      status,
      content,
    });
  }

  render() {
    const { status, title, content } = this.state;

    return (
      <Step status={status} isInstantActivationEnabled={this.props.user.isInstantActivationEnabled}>
        <StepTitle>{title}</StepTitle>
        <StepContent>{content}</StepContent>
      </Step>
    );
  }
}

export default compose(
  connect(null, { showProductsModal }),
  rTracking(() => {
    return window.rzpQ.component('TestMode');
  }),
)(TestMode);

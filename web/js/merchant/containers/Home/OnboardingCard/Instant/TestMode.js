import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

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

export default class TestMode extends Component {
  constructor(props) {
    super(props);
    this.state = initialState;
  }

  componentWillReceiveProps(nextProps) {
    const { mode, integration, showProductsModal } = nextProps,
      { isLoading, keysGenerated, paymentsMade } = integration;

    let { title, status, content } = initialState;

    if (mode === 'live') {
      content = 'You can try out the Dashboard in Test Mode';
    } else if (isLoading) {
      status = possibleStatuses.loading;
    } else {
      if (paymentsMade) {
        title = 'Test Mode Payments';
        content = (
          <span>
            View all payments received in Test mode in{' '}
            <Link to="/payments">Transactions</Link> tab
          </span>
        );
      } else if (!keysGenerated) {
        content = (
          <span>
            <Link to="/keys" className="btn-link">
              Generate Test Keys
            </Link>{' '}
            and use <TestProducts onClick={showProductsModal} />
          </span>
        );
      } else {
        title = 'Transact in Test Mode';
        content = (
          <span>
            Create Test payments now. For details, Read{' '}
            <a
              target="_blank"
              className="btn-link"
              href="https://docs.razorpay.com/"
            >
              documentation
            </a>{' '}
            or use <TestProducts onClick={showProductsModal} />
          </span>
        );
      }
    }

    if (
      this.onActive &&
      status !== this.state.status &&
      status === possibleStatuses.active
    ) {
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
      <Step status={status}>
        <StepTitle>{title}</StepTitle>
        <StepContent>{content}</StepContent>
      </Step>
    );
  }
}

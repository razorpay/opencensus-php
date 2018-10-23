import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Button from 'component/Button';
import { LIVE_MODE } from 'merchant/containers/Home/OnboardingCard/data';
import { switchToLive } from 'merchant/containers/Home/OnboardingCard/SwitchToLive';

import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

const initialState = {
  status: possibleStatuses.locked,
  title: 'Live Payments',
  content: null,
};

export default class LiveMode extends Component {
  constructor(props) {
    super(props);

    this.state = initialState;
    this.switchToLive = () => switchToLive(props.merchantId);
  }

  componentWillReceiveProps(nextProps) {
    const {
        mode,
        instantActivation,
        isActivated,
        isSubmitted,
        isRejected,
        integration,
        showProductsModal,
        showTransactionsModal,
        onActive,
      } = nextProps,
      { isLoading, keysGenerated, paymentsMade, isKLA } = integration,
      { isL1Submitted, isGraylistFlow, isBlacklistFlow } = instantActivation;

    let { title, status, content } = initialState;

    if (!isActivated) {
      if (!isL1Submitted) {
        content = 'Fill the Activation Form in order to unlock Live Payments';
      } else if (isGraylistFlow) {
        if (!isSubmitted) {
          content = 'Fill the KYC Form in order to unlock Live Payments';
        } else {
          content =
            'Live payments will be enabled after your KYC form is verified';
        }
      } else if (isBlacklistFlow) {
        status = possibleStatuses.blocked;
        title += ' (Locked)';
        content = 'We currently do not support your business model';
      }
    } else {
      if (mode !== LIVE_MODE) {
        title = 'Accept Live Payments';
        status = possibleStatuses.active;
        content = (
          <div>
            <div>
              Accept payments in Live mode by integrating or using other
              products
            </div>
            <button className="btn btn-primary m-t" onClick={this.switchToLive}>
              Take me to live mode
            </button>
          </div>
        );
      } else {
        if (isRejected) {
          status = possibleStatuses.blocked;
          content =
            'Transactions are not allowed as your account has been blocked';
        } else {
          if (isLoading) {
            status = possibleStatuses.loading;
          } else {
            if (!paymentsMade) {
              status = possibleStatuses.active;
              title = 'Transact in Live Mode';
              content = (
                <div>
                  <div>
                    Receive payments by integrating in Live mode or view
                    products
                  </div>
                  <button
                    className="btn btn-primary m-t"
                    onClick={() => showTransactionsModal(isKLA)}
                  >
                    How do I accept payments?
                  </button>
                </div>
              );
            } else {
              status = possibleStatuses.done;
              content = (
                <div>
                  View payments in{' '}
                  <Link to="/payments" className="btn-link">
                    Transactions
                  </Link>{' '}
                  tab, or keep using products.
                  <div className="m-t">
                    <Button.Secondary onClick={() => showProductsModal()}>
                      View Products
                    </Button.Secondary>
                  </div>
                </div>
              );
            }
          }
        }
      }
    }

    if (
      onActive &&
      status !== this.state.status &&
      (status === possibleStatuses.active || status === possibleStatuses.done)
    ) {
      onActive();
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

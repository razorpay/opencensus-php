import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Button from 'common/new-ui/Button';
import { LIVE_MODE } from 'merchant/containers/Home/OnboardingCard/data';
import { switchToMode } from 'merchant/containers/Home/OnboardingCard/SwitchToMode';
import analyticsService from '@commander/services/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

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
    this.switchToLive = () => {
      props.track.switchToLive();
      switchToMode(props.merchantId, 'live');

      window.trackHubs({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
    };
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
        track,
        internationalActivationFlow,
        locked,
      } = nextProps,
      { isLoading, keysGenerated, paymentsMade, isKLA } = integration,
      {
        isL1Submitted,
        isGraylistFlow,
        isBlacklistFlow,
        isUnregisteredBusiness,
      } = instantActivation;

    let { title, status, content } = initialState;

    if (!isActivated) {
      if (!isL1Submitted && !locked) {
        content = (
          <span>
            <Link
              to="/activation"
              className="btn-link"
              onClick={() => {
                track.fillActivationForm();
                analyticsService.track({
                  objectName: 'SignUp',
                  actionName: 'Fill KYC CTA clicked',
                  screen: 'home page',
                  properties: {
                    ...getCommonSegmentProperties(),
                  },
                });
              }}
            >
              Fill the Activation Form
            </Link>{' '}
            in order to unlock Live Payments
          </span>
        );
      } else if (!!locked && !isActivated) {
        content = <span>Complete activation in order to unlock live payments</span>;
      } else if (isGraylistFlow) {
        if (!isSubmitted) {
          if (internationalActivationFlow.isGraylistFlow) {
            content = this.internationalGreylistContent;
          } else {
            content = (
              <span>
                <Link to="/activation" className="btn-link" onClick={() => track.fillKYCForm()}>
                  Fill KYC Form
                </Link>
                &nbsp; to complete verification and get live payments enabled for your account.
              </span>
            );
          }
        } else {
          content = 'Live payments will be enabled after your KYC form is verified';
        }
      } else if (isBlacklistFlow) {
        status = possibleStatuses.blocked;
        content = 'We currently do not support your business model';
      }
    } else {
      if (mode !== LIVE_MODE) {
        title = 'Accept Live Payments';
        status = possibleStatuses.active;
        content = (
          <div>
            <div>Accept payments in Live mode by integrating or using other products</div>
            <button className="btn btn-primary m-t" onClick={this.switchToLive}>
              Take me to live mode
            </button>
          </div>
        );
      } else {
        if (isRejected) {
          status = possibleStatuses.blocked;
          content = 'Transactions are not allowed as your account has been suspended';
        } else {
          if (isLoading) {
            status = possibleStatuses.loading;
          } else {
            if (!paymentsMade) {
              status = possibleStatuses.active;
              title = 'Transact in Live Mode';
              content = (
                <div>
                  <div>Receive payments by integrating in Live mode or view products</div>
                  <button
                    className="btn btn-primary m-t"
                    onClick={() => (track.howDoIAcceptPayments(), showTransactionsModal(isKLA))}
                  >
                    How do I accept payments?
                  </button>
                </div>
              );
            } else {
              status = possibleStatuses.done;
              content = (
                <div>
                  Track live payments in the{' '}
                  <Link
                    to="/payments"
                    className="btn-link"
                    onClick={() => track.viewTransactions()}
                  >
                    Transactions
                  </Link>{' '}
                  tab
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

  get internationalGreylistContent() {
    const { track } = this.props;
    return (
      <span>
        <Link to="/activation" className="btn-link" onClick={() => track.fillKYCForm()}>
          Fill KYC Form
        </Link>
        &nbsp; to unlock Live domestic payments. Complete KYC verification to unlock international
        payments.
      </span>
    );
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

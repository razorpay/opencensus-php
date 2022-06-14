import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { LIVE_MODE } from 'merchant/containers/Home/OnboardingCard/data';
import { switchToMode } from 'merchant/containers/Home/OnboardingCard/SwitchToMode';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';

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

      window.trackHubs?.({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const {
        mode,
        instantActivation,
        isActivated,
        isSubmitted,
        isRejected,
        integration,
        showTransactionsModal,
        onActive,
        track,
        internationalActivationFlow,
        locked,
        user,
      } = nextProps,
      { isLoading, paymentsMade, isKLA } = integration,
      { isL1Submitted, isGraylistFlow, isBlacklistFlow } = instantActivation;

    let { title, status, content } = initialState;

    const activationState = getActivationState(user, user.isUnregisteredBusiness);

    if (user.isInstantActivationEnabled) {
      switch (activationState) {
        case 'funds_on_hold': {
          title = 'Live payments and Settlements';
          status = possibleStatuses.blocked;
          content = (
            <div>
              You can keep accepting payments{' '}
              <a
                className="btn-link"
                target="_blank"
                rel="noopener noreferrer"
                href="http://razorpay.com/settlement"
              >
                settlements
              </a>
              will be enabled after successful KYC review
            </div>
          );

          break;
        }
        case 'account_activated': {
          title = 'Account Activation';
          status = possibleStatuses.done;
          content = (
            <div>
              Your account has been activated successfully and your settlements are completely
              activated
            </div>
          );
          break;
        }
        case 'activated_mcc_pending_with_tnc': {
          title = 'Account Activation';
          status = possibleStatuses.active;
          content = (
            <div>
              Your account will be activated once compliance checks are complete. Settlements might
              be paused if we need more details for review
            </div>
          );
          break;
        }
        case 'activated_mcc_pending_without_tnc': {
          title = 'Account Activation';
          status = possibleStatuses.active;
          content = <div>Your account will be activated once your KYC review is complete.</div>;
          break;
        }

        case 'rejected': {
          title = 'Account Activation';
          status = possibleStatuses.locked;
          content = (
            <div>Account cannot be activated as we currently cannot support your business</div>
          );
          break;
        }

        case 'needs_clarification_funds_on_hold':
        case 'needs_clarification_mcc_pending': {
          title = 'Account Activation';
          status = possibleStatuses.active;
          content = <div>Your account will be activated once your KYC review is complete.</div>;
          break;
        }

        default: {
          title = 'Account Activation';
          status = possibleStatuses.locked;
          content = <div>Get all the KYC details approved to complete the account activation</div>;
          break;
        }
      }
    } else {
      if (!isActivated) {
        if (isL1Submitted && !locked) {
          content = (
            <span>
              <Link
                to="/activation"
                className="btn-link"
                onClick={() => {
                  track.fillActivationForm();
                  analyticsTrack({
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
      <Step status={status} isInstantActivationEnabled={this.props.user.isInstantActivationEnabled}>
        <StepTitle>{title}</StepTitle>
        <StepContent>{content}</StepContent>
      </Step>
    );
  }
}

/* eslint-disable react/jsx-key */
import React, { Component } from 'react';

import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import DataList from 'merchant/components/OnBoarding/Slides/DataList';
import Banner from 'merchant/views/Capital/components/Banner';

import LeadDetails from './LeadDetails';

const PROS = [
  <div className="flex">
    <img src={require("assets/capital/internal_credit.svg")} alt="landing-image" />
    <div className="p-l m-l m-t">
      <strong>
        <p>Flexible Credit Limit</p>
      </strong>
      <p className="privilege-description">
        Razorpay's Credit Decisioning System sets a higher credit limit based on timely repayments.
      </p>
    </div>
  </div>,
  <div className="flex m-t">
    <img src={require("assets/capital/auto_repayment.svg")} alt="landing-image" />
    <div className="p-l m-l m-t">
      <strong>
        <p>Auto Repayment</p>
      </strong>
      <p className="privilege-description">
        Repay automatically through settlements just like it is an advance of your settlements.
      </p>
    </div>
  </div>,
  <div className="flex m-t">
    <img src={require("assets/capital/flexible_interest.svg")} alt="landing-image" />
    <div className="p-l m-l m-t">
      <strong>
        <p>Pay Interest only on your use </p>
      </strong>
      <p className="privilege-description">
        Pay interest only on the amount withdrawn for the duration of the withdrawal.
      </p>
    </div>
  </div>,
];

class Onboarding extends Component {
  render() {
    const {
      createFDTicket,
      onRaiseRequest,
      leadGenerated,
      hasWithdrawalConfiguration,
      hasLOCStage2Feature,
      withdrawalConfiguration,
    } = this.props;

    return (
      <OnBoardingWrapper className="Withdrawals">
        <div className="Landing--Image">
          <div className="image-wrapper">
            <img src={require("assets/capital/withdrawal_landing.svg")} alt="landing-image" />
          </div>
        </div>
        <div className="Product--Details">
          <div className="Details-title">
            Cash Advance
            <div className="divider" />
          </div>
          <div className="Details-desc">
            Withdraw money up to your credit limit, repay when customers pay and borrow again when
            you need cash.
          </div>
          <hr />
          <DataList>{PROS}</DataList>
        </div>

        <div
          className={`right-floating-card loan-application-home ${
            hasLOCStage2Feature && hasWithdrawalConfiguration ? 'withdrawal-config-container' : ''
          }`}
        >
          {hasLOCStage2Feature && hasWithdrawalConfiguration && (
            <div className="banner-wrapper">
              <Banner
                title="Congratulations!"
                description={
                  <div>
                    <p>
                      Your cash advance application has been successfully approved!
                      <br />
                      Process your first withdrawal to boost your business.
                    </p>
                  </div>
                }
                type="success"
                isFormHeader={true}
              />
              <img src={require("assets/capital/green_patch.svg")} className="green_patch" />
            </div>
          )}
          {hasLOCStage2Feature && hasWithdrawalConfiguration && (
            <div className="withdrawal-form-container p-all m-all">
              <div className="withdrawal-config-details">
                <EntityDetailRow
                  pairClass="highlight"
                  label={
                    <div>
                      <p>Maximum Withdrawable amount</p>
                      <p className="text-small text-faded">in a single transaction</p>
                    </div>
                  }
                >
                  <h4>
                    <Amount value={withdrawalConfiguration.configuration.max_withdraw_amount} />
                  </h4>
                </EntityDetailRow>
                <EntityDetailRow
                  label={
                    <div>
                      <p>Total Withdrawable Balance</p>
                      <p className="text-small text-faded">as a credit limit</p>
                    </div>
                  }
                >
                  <h4>
                    <Amount value={withdrawalConfiguration.configuration.internal_credit_limit} />
                  </h4>
                </EntityDetailRow>
                <EntityDetailRow
                  label={
                    <div>
                      <p>Repayment Method</p>
                    </div>
                  }
                >
                  <div className="text-right">
                    <p>From your settlement Balance</p>
                    <p className="text-small text-faded">Repayment amount will be deducted</p>
                  </div>
                </EntityDetailRow>
              </div>
              <div className="p-all text-center m-all">
                <Button.Primary
                  onClick={() => {
                    this.props.trackGA({
                      eventAction: 'Flash Credit Tab',
                      eventLabel: 'Apply | Start your First Withdrawal',
                    });
                    this.props.history.push('/capital/cash-advance/withdrawals');
                  }}
                >
                  Start your First withdrawal
                </Button.Primary>
              </div>
            </div>
          )}
          {!hasLOCStage2Feature && !hasWithdrawalConfiguration && leadGenerated && (
            <div className="withdrawal-form-container">
              <div className="lead-generation-message-container text-center">
                <img src={require("assets/capital/lead_generated.svg")} alt="landing-image" />
                <p className="m-t">
                  We have successfully collected your details, Our team will reach you back to take
                  forward your application.
                </p>
              </div>
            </div>
          )}

          {!hasLOCStage2Feature && !hasWithdrawalConfiguration && !leadGenerated && (
            <div className="status-overview">
              <div className="loan-application-overview-header flex">
                <div className="loan-meta-wrapper">
                  <h4>
                    <strong>Cash Advance</strong>
                  </h4>
                  <p className="text--secondary">
                    Share your basic details here, So that we will get back to you.
                  </p>
                </div>
              </div>
              <div className="m-all p-l">
                <hr className="no-margin" />
              </div>
              <div className="withdrawal-form-container">
                <LeadDetails createFDTicket={createFDTicket} onRaiseRequest={onRaiseRequest} />
              </div>
            </div>
          )}
          <div className="p-l p-r footer">
            <div className="btn-toolbar">
              <a
                className="m-l link"
                href="https://razorpay.com/capital/cash-advance/#faqs"
                target="_blank"
                rel="noreferrer noopener"
              >
                <strong>Show FAQ's</strong>
                <i className="i i-question-circle-o m-l" />
              </a>
            </div>
            <img src={require("assets/capital/capital_logo.svg")} alt="Loading icon" />
          </div>
        </div>
      </OnBoardingWrapper>
    );
  }
}

export default withRouter(Onboarding);

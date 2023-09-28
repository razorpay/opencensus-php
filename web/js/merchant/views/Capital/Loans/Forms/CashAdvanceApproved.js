import React, { Component } from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import {
  APPLICATION_STATES,
  CAPITAL_PRODUCT_CODES,
  HOTJAR_TRIGGERS,
  TOOLTIP_DESCRIPTIONS,
} from 'merchant/views/Capital/Loans/constants';
import CreditOffer from 'merchant/views/Capital/components/CreditOffer';
import { FormLoader } from 'merchant/views/Capital/components/FormSectionLoadingSkeleton';
import { closeModal } from 'merchant_common/reducers/modals';

const trackMouseOver = () => {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - WCL LOS',
    eventAction: 'TOOLTIP | IFSC CODE',
    eventLabel: 'Cash-Advance Offer Approved',
  });
};

const TABS = {
  WITHDRAWAL_DETAILS: 'WITHDRAWAL_DETAILS',
  REPAYMENT_DETAILS: 'REPAYMENT_DETAILS',
};

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    closeModal,
  },
)
class CashAdvanceApproved extends Component {
  constructor() {
    super();
    this.state = {
      activeTab: null,
    };
  }
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_FINAL_APPROVAL);
  }

  setActiveTab = (tab) => {
    this.setState((prevState) => ({
      activeTab: prevState.activeTab === tab ? null : tab,
    }));
  };

  redirectToWithdrawals = () => {
    const { closeModal, history } = this.props;

    closeModal();
    history.push('/capital/cash-advance/');
  };

  render() {
    const { user, loanApplicationDetails } = this.props;

    const { credit_offer_details, accepted_offer_details } = loanApplicationDetails;

    if (
      credit_offer_details.loading ||
      !credit_offer_details.data ||
      accepted_offer_details.loading
    )
      return <FormLoader />;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    );

    return (
      <div class="credit-offer-container no-padding">
        <div className="loan-offer-wrapper">
          <div class="block-note success m-b">
            <strong>Credit Offer Details</strong>
            <div>
              <span class="text-small">
                The one time fees will be charged after the application approval
              </span>
            </div>
          </div>
          <CreditOffer
            offerDetails={creditOffer}
            highlightCreditAmount={false}
            approved={false}
            _fromWhere="Loan Approved"
            product={CAPITAL_PRODUCT_CODES.CASH_ADVANCE}
          />
        </div>
        <div class="loan-offer-wrapper m-t m-b">
          <div className="block-note warning">
            <strong>Withdrawal Details</strong>
            <div class="pull-right">
              <Button.Transparent onClick={() => this.setActiveTab(TABS.WITHDRAWAL_DETAILS)}>
                {this.state.activeTab === TABS.WITHDRAWAL_DETAILS ? 'Hide Details' : 'View Details'}
                <i
                  className={`i i-chevron-${
                    this.state.activeTab === TABS.WITHDRAWAL_DETAILS ? 'up' : 'down'
                  }`}
                />
              </Button.Transparent>
            </div>
          </div>
          {this.state.activeTab === TABS.WITHDRAWAL_DETAILS && (
            <div className="m-t loan-offer-details-wrapper">
              <div className="section loan-offer-summary-section">
                <div>
                  <span className="no-padding loan-offer-detail-title">
                    Opening Withdrawable Balance&nbsp;
                    <small className="help-content">
                      <i className="i i-info-outline" onMouseOver={trackMouseOver} />
                      <Popover align="top" theme="dark">
                        <PopoverBody>
                          <div className="text-left">
                            {TOOLTIP_DESCRIPTIONS.ca_internal_credit_limit}
                          </div>
                        </PopoverBody>
                      </Popover>
                    </small>
                  </span>
                  <div>
                    <span className="text-small text-faded">
                      This value will be increased upon regular repayments
                    </span>
                  </div>
                </div>
                <div className="wc-amount">
                  <Amount value={creditOffer.max_credit_offered} />
                </div>
              </div>

              <hr />

              <div className="section loan-offer-summary-section">
                <div>
                  <span className="no-padding loan-offer-detail-title">
                    Maximum Withdrawable Amount
                  </span>
                  <div>
                    <span className="text-small text-faded">in a single transaction</span>
                  </div>
                </div>
                <div className="wc-amount">
                  <Amount value={creditOffer.withdrawal_limit_per_request} />
                </div>
              </div>

              <hr />

              <div className="section disbursal-details">
                <strong>
                  Disbursal Details&nbsp;
                  <small className="help-content">
                    <i className="i i-info-outline" onMouseOver={trackMouseOver} />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div class="text-left">{TOOLTIP_DESCRIPTIONS.disbursing_account}</div>
                      </PopoverBody>
                    </Popover>
                  </small>
                </strong>
              </div>
              <div className="section loan-offer-summary-section">
                <div className="loan-offer-summary-wrapper">
                  <p className="loan-offer-summary-title">Account Number</p>
                  <p className="loan-offer-value">{user.bank_account_number}</p>
                </div>
                <vr />
                <div className="loan-offer-summary-wrapper">
                  <p className="loan-offer-summary-title">
                    IFSC Code&nbsp;
                    <small className="help-content">
                      <i className="i i-info-outline" onMouseOver={trackMouseOver} />
                      <Popover align="top" theme="dark">
                        <PopoverBody>
                          <div class="text-left">{TOOLTIP_DESCRIPTIONS.ifsc_code}</div>
                        </PopoverBody>
                      </Popover>
                    </small>
                  </p>
                  <p className="loan-offer-value no-padding">{user.bank_branch_ifsc}</p>
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="loan-offer-wrapper m-t m-b">
          <div className="block-note success">
            <strong>Repayment Details</strong>
            <div className="pull-right">
              <Button.Transparent onClick={() => this.setActiveTab(TABS.REPAYMENT_DETAILS)}>
                {this.state.activeTab === TABS.REPAYMENT_DETAILS ? 'Hide Details' : 'View Details'}
                <i
                  className={`i i-chevron-${
                    this.state.activeTab === TABS.REPAYMENT_DETAILS ? 'up' : 'down'
                  }`}
                />
              </Button.Transparent>
            </div>
          </div>
          {this.state.activeTab === TABS.REPAYMENT_DETAILS && (
            <div className="m-t loan-offer-details-wrapper">
              <div className="section loan-offer-summary-section">
                <div>
                  <strong>Repayment Method</strong>
                  <p>
                    From your settlement Balance, Repayable amount will be deducted from the next
                    day of withdrawal on daily basis.
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
        <div class="loan-offer-wrapper">
          <div className="loan-offer-action pull-right">
            <Button.Transparent
              onClick={() => {
                this.props._trackNavigationActions(
                  'BACK',
                  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
                );
                this.props.navigation.back();
              }}
            >
              <i className="i i-chevron-left" />
              Back
            </Button.Transparent>
            <Button.Primary onClick={this.redirectToWithdrawals}>
              Continue Withdrawing
              <i className="i i-chevron-right" />
            </Button.Primary>
          </div>
        </div>
      </div>
    );
  }
}

export default withRouter(CashAdvanceApproved);

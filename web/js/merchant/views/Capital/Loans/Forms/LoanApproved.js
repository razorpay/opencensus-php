import React, { Component } from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';

import CreditOffer from '../../components/CreditOffer';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import RepaymentInformation from '../../components/RepaymentInformation';
import SettlementAccountDetails from '../../components/SettlementAccountDetails';
import { isPreceedingState } from '../../utils';
import { APPLICATION_STATES, CAPITAL_PRODUCT_CODES, HOTJAR_TRIGGERS } from '../constants';

class LoanApproved extends Component {
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_FINAL_APPROVAL);
  }

  render() {
    const { credit_offer_details, accepted_offer_details, meta } =
      this.props.loanApplicationDetails;

    if (credit_offer_details.loading || accepted_offer_details.loading) return <FormLoader />;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    );

    return (
      <div className="credit-offer-container">
        <div className="loan-offer-wrapper">
          <CreditOffer
            offerDetails={creditOffer}
            approved={true}
            _fromWhere="Loan Approved"
            product={meta.product}
            highlightCreditAmount={false}
          />
          <div className="m-b">
            <SettlementAccountDetails
              user={this.props.user}
              showFinancerDetails={false}
              _fromWhere="Loan Approved"
            />
          </div>
          <RepaymentInformation
            creditOffer={creditOffer}
            _fromWhere="Loan Approved"
            product={CAPITAL_PRODUCT_CODES.LOAN}
          />
        </div>
        <div className="loan-offer-wrapper">
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
            {!isPreceedingState(meta.data.application.status, APPLICATION_STATES.RZP_APPROVED) && (
              <Button.Primary
                onClick={() => {
                  this.props._trackNavigationActions('NEXT', this.props.nextState);
                  this.props.navigation.next();
                }}
              >
                Next
                <i className="i i-chevron-right" />
              </Button.Primary>
            )}
          </div>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
  },
)(LoanApproved);

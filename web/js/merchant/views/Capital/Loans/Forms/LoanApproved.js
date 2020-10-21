import React, { Component } from 'react';
import CreditOffer from '../../components/CreditOffer';
import RepaymentInformation from '../../components/RepaymentInformation';
import Button from 'common/new-ui/Button';
import { connect } from 'react-redux';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import SettlementAccountDetails from '../../components/SettlementAccountDetails';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES, CAPITAL_PRODUCT_CODES, HOTJAR_TRIGGERS } from '../constants';
import { isPreceedingState } from '../../utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
  },
)
class LoanApproved extends Component {
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_FINAL_APPROVAL);
  }

  render() {
    const {
      credit_offer_details,
      accepted_offer_details,
      meta,
    } = this.props.loanApplicationDetails;

    if (credit_offer_details.loading || accepted_offer_details.loading) return <FormLoader />;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    );

    return (
      <div class={'credit-offer-container'}>
        <div className="loan-offer-wrapper">
          <CreditOffer
            offerDetails={creditOffer}
            approved={true}
            _fromWhere="Loan Approved"
            product={meta.product}
            highlightCreditAmount={false}
          />
          <div class="m-b">
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

export default LoanApproved;

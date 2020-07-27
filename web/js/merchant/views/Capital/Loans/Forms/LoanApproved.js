import React, { Component } from 'react';
import CreditOffer from '../../components/CreditOffer';
import RepaymentInformation from '../../components/RepaymentInformation';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { connect } from 'react-redux';
import {
  fetchLoanApplicationMeta,
  changeActiveState,
} from 'merchant/reducers/capital';
import SettlementAccountDetails from '../../components/SettlementAccountDetails';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES } from '../constants';
import { isPreceedingState } from '../../utils';

@connect(
  state => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    changeActiveState,
  }
)
class LoanApproved extends Component {
  render() {
    const {
      credit_offer_details,
      accepted_offer_details,
      meta,
    } = this.props.loanApplicationDetails;

    if (credit_offer_details.loading || accepted_offer_details.loading)
      return <FormLoader />;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      credit_offer => credit_offer.id === acceptedCreditOfferId
    );

    return (
      <div class={'credit-offer-container'}>
        <div className="loan-offer-wrapper">
          <CreditOffer
            offerDetails={creditOffer}
            approved={true}
            _fromWhere="Loan Approved"
          />
          <div class="m-b">
            <SettlementAccountDetails
              user={this.props.user}
              showFinancerDetails={false}
              _fromWhere="Loan Approved"
            />
          </div>
          <RepaymentInformation
            amount={creditOffer.installment.amount}
            _fromWhere="Loan Approved"
          />
        </div>
        <div className="loan-offer-action pull-right">
          <Button.Transparent
            onClick={() => {
              this.props._trackNavigationActions(
                'BACK',
                APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW
              );
              this.props.changeActiveState(
                APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW
              );
            }}
          >
            <i className="i i-chevron-left" />
            Back
          </Button.Transparent>
          {!isPreceedingState(
            meta.data.application.status,
            APPLICATION_STATES.CREDIT_DISBURSED
          ) && (
            <Button.Primary
              class="no-margin"
              onClick={() => {
                this.props._trackNavigationActions(
                  'NEXT',
                  APPLICATION_STATES.CREDIT_DISBURSED
                );
                this.props.changeActiveState(
                  APPLICATION_STATES.CREDIT_DISBURSED
                );
              }}
            >
              Next
              <i className="i i-chevron-right" />
            </Button.Primary>
          )}
        </div>
      </div>
    );
  }
}

export default LoanApproved;

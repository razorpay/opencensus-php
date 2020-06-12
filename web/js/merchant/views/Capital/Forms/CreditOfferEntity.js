import React, { Component } from 'react';
import { connect } from 'react-redux';
import CreditOffer from '../components/CreditOffer';
import {
  acceptCreditOffer,
  fetchCreditOffers,
  getAcceptedOffer,
  changeActiveState,
  fetchLoanApplicationMeta,
} from 'merchant/reducers/capital';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import RepaymentInformation from '../components/RepaymentInformation';
import { FormLoader } from '../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES } from '../constants';

@connect(
  state => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    acceptCreditOffer,
    fetchCreditOffers,
    getAcceptedOffer,
    changeActiveState,
    fetchLoanApplicationMeta,
  }
)
class CreditOfferEntity extends Component {
  handleAcceptance = creditOfferId => {
    console.log(creditOfferId, this.props.loanApplicationDetails.meta);
    const data = {
      id: creditOfferId,
      application_id: this.props.loanApplicationDetails.meta.data.application
        .id,
    };
    return this.props.acceptCreditOffer(data).then(response => {
      if (response && !response.errors) {
        return Promise.all([
          this.props.fetchLoanApplicationMeta(
            this.props.loanApplicationDetails.meta.data.application.id
          ),
          this.props.fetchCreditOffers({
            application_id: this.props.loanApplicationDetails.meta.data
              .application.id,
          }),
          this.props.getAcceptedOffer({
            application_id: this.props.loanApplicationDetails.meta.data
              .application.id,
          }),
        ]);
      }
    });
  };

  render() {
    const {
      credit_offer_details,
      accepted_offer_details,
    } = this.props.loanApplicationDetails;

    if (credit_offer_details.loading) return <FormLoader />;

    if (!credit_offer_details.data.credit_offers)
      return 'No Credit offers' + ' found';

    //TODO:take the latest offer
    const creditOffer =
      credit_offer_details.data.credit_offers[
        credit_offer_details.data.credit_offers.length - 1
      ];
    console.log(creditOffer);

    return (
      <div class={'credit-offer-container'}>
        <div className="loan-offer-wrapper">
          <CreditOffer offerDetails={creditOffer} />
          <RepaymentInformation amount={creditOffer.installment.amount} />
          {!(
            accepted_offer_details.data &&
            accepted_offer_details.data.credit_offer_id
          ) ? (
            <div className="loan-offer-action">
              <div className="help-text">
                <span className="callback-text">Need help! Arrange a</span>
                <a
                  className="text-primary"
                  target="_blank"
                  href="https://razorpay.com/terms/"
                >
                  Callback
                </a>
              </div>
              <AsyncBtn.Primary
                type="submit"
                class="btn btn-primary pull-right no-margin"
                onClick={() => this.handleAcceptance(creditOffer.id)}
              >
                Accept Offer
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          ) : (
            <div className="loan-offer-action">
              <button
                class="btn btn-link"
                onClick={() =>
                  this.props.changeActiveState(
                    APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS
                  )
                }
              >
                <i className="i i-chevron-left" />
                Back
              </button>
              <AsyncBtn.Primary
                type="submit"
                class="no-margin"
                onClick={() =>
                  this.props.changeActiveState(
                    APPLICATION_STATES.CONTRACT_PENDING
                  )
                }
              >
                Next
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default CreditOfferEntity;

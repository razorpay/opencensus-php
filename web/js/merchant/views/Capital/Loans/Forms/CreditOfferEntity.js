import React, { Component } from 'react';
import { connect } from 'react-redux';
import CreditOffer from '../../components/CreditOffer';
import {
  acceptCreditOffer,
  fetchCreditOffers,
  getAcceptedOffer,
  changeActiveState,
  fetchLoanApplicationMeta,
} from 'merchant/reducers/capital';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import RepaymentInformation from '../../components/RepaymentInformation';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES, HOTJAR_TRIGGERS } from '../constants';

@connect(
  (state) => ({
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
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_LOAN_OFFER);
  }

  handleAcceptance = (creditOfferId) => {
    const data = {
      id: creditOfferId,
      application_id: this.props.loanApplicationDetails.meta.data.application.id,
    };
    return this.props.acceptCreditOffer(data).then((response) => {
      if (response && !response.errors) {
        this.props._trackEvent({
          eventAction: 'Application | Accept Offer',
          eventLabel: 'Complete Application | Loan Offer',
        });
        return Promise.all([
          this.props.fetchLoanApplicationMeta(
            this.props.loanApplicationDetails.meta.data.application.id
          ),
          this.props.fetchCreditOffers({
            application_id: this.props.loanApplicationDetails.meta.data.application.id,
          }),
          this.props.getAcceptedOffer({
            application_id: this.props.loanApplicationDetails.meta.data.application.id,
          }),
        ]);
      }
    });
  };

  handleBack = () => {
    this.props._trackNavigationActions('BACK', APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS);
    this.props.changeActiveState(APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS);
  };

  render() {
    const { credit_offer_details, accepted_offer_details } = this.props.loanApplicationDetails;

    if (credit_offer_details.loading) return <FormLoader />;

    if (!credit_offer_details.data.credit_offers) return 'No Credit offers' + ' found';

    //TODO:take the latest offer
    const creditOffer =
      credit_offer_details.data.credit_offers[credit_offer_details.data.credit_offers.length - 1];

    return (
      <div class={'credit-offer-container'}>
        <div className="loan-offer-wrapper">
          <CreditOffer offerDetails={creditOffer} _fromWhere="Loan Offer" />
          <RepaymentInformation amount={creditOffer.installment.amount} _fromWhere="Loan Offer" />
          {!(accepted_offer_details.data && accepted_offer_details.data.credit_offer_id) ? (
            <div className="loan-offer-action">
              <Button.Transparent onClick={this.handleBack}>
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
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
              <Button.Transparent onClick={this.handleBack}>
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
              <AsyncBtn.Primary
                type="submit"
                class="no-margin"
                onClick={() => {
                  this.props._trackNavigationActions('NEXT', APPLICATION_STATES.CONTRACT_PENDING);
                  this.props.changeActiveState(APPLICATION_STATES.CONTRACT_PENDING);
                }}
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

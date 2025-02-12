import { Component } from 'react';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import {
  acceptCreditOffer,
  fetchCreditOffers,
  fetchLoanApplicationMeta,
  getAcceptedOffer,
} from 'merchant/reducers/capital';

import CreditOffer from '../../components/CreditOffer';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import RepaymentInformation from '../../components/RepaymentInformation';
import { isCashAdvanceProduct } from '../../utils';
import { HOTJAR_TRIGGERS } from '../constants';

class CreditOfferEntity extends Component {
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_LOAN_OFFER);
  }

  handleAcceptance = (creditOfferId) => {
    const { meta } = this.props.loanApplicationDetails;

    const data = {
      id: creditOfferId,
      application_id: meta.data.application.id,
    };
    return this.props.acceptCreditOffer(data, meta.product).then((response) => {
      if (response && !response.errors) {
        this.props._trackEvent({
          eventAction: 'Application | Accept Offer',
          eventLabel: `Complete Application | ${meta.product} Offer`,
        });
        return Promise.all([
          this.props.fetchLoanApplicationMeta(
            this.props.loanApplicationDetails.meta.data.application.id,
          ),
          this.props.fetchCreditOffers({
            application_id: this.props.loanApplicationDetails.meta.data.application.id,
          }),
          this.props.getAcceptedOffer({
            application_id: this.props.loanApplicationDetails.meta.data.application.id,
          }),
        ]);
      }
      return null;
    });
  };

  handleBack = () => {
    this.props._trackNavigationActions('BACK', this.props.previousState);
    this.props.navigation.back();
  };

  render() {
    const { credit_offer_details, accepted_offer_details, meta } =
      this.props.loanApplicationDetails;

    if (credit_offer_details.loading || !credit_offer_details.data) return <FormLoader />;

    if (!credit_offer_details.data.credit_offers) return 'No Credit offers found';

    const creditOffer =
      credit_offer_details.data.credit_offers[credit_offer_details.data.credit_offers.length - 1];

    const isOfferAccepted =
      accepted_offer_details.data && accepted_offer_details.data.credit_offer_id;

    const showRepaymentInfo = !isCashAdvanceProduct(meta.product);
    return (
      <div className="credit-offer-container">
        <div className="loan-offer-wrapper">
          <CreditOffer
            offerDetails={creditOffer}
            _fromWhere={`${meta.product} Offer`}
            product={meta.product}
          />
          {showRepaymentInfo && (
            <RepaymentInformation
              product={meta.product}
              creditOffer={creditOffer}
              _fromWhere={`${meta.product} Offer`}
            />
          )}
          {!isOfferAccepted ? (
            <div className="loan-offer-action">
              <Button.Transparent onClick={this.handleBack}>
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
              <AsyncBtn.Primary
                type="submit"
                className="btn btn-primary pull-right"
                onClick={() => this.handleAcceptance(creditOffer.id)}
              >
                Accept Offer
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          ) : (
            <div className="loan-offer-action">
              <Button.Transparent onClick={this.handleBack} className="back-btn">
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
              <AsyncBtn.Primary
                type="submit"
                onClick={() => {
                  this.props._trackNavigationActions('NEXT', this.props.nextState);
                  this.props.navigation.next();
                }}
              >
                Next
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          )}
          {isOfferAccepted ? (
            <div className="credit-offer__meta">
              <hr />
              <p>Loan sanction letter will be sent on your registered email address</p>
            </div>
          ) : null}
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    acceptCreditOffer,
    fetchCreditOffers,
    getAcceptedOffer,
    fetchLoanApplicationMeta,
  },
)(CreditOfferEntity);

import React, { Component } from 'react';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import Button from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import {
  acceptCreditOffer,
  fetchCreditOffers,
  fetchLoanApplicationMeta,
  getAcceptedOffer,
} from 'merchant/reducers/capital';
import {
  APPLICATION_STATES,
  CAPITAL_PRODUCT_CODES,
  HOTJAR_TRIGGERS,
} from 'merchant/views/Capital/Loans/constants';
import CreditOffer from 'merchant/views/Capital/components/CreditOffer';
import { FormLoader } from 'merchant/views/Capital/components/FormSectionLoadingSkeleton';
import RepaymentInformation from 'merchant/views/Capital/components/RepaymentInformation';
import RepaymentModal from 'merchant/views/Capital/components/RepaymentModal';
import SettlementAccountDetails from 'merchant/views/Capital/components/SettlementAccountDetails';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    acceptCreditOffer,
    fetchCreditOffers,
    getAcceptedOffer,
    fetchLoanApplicationMeta,
    openModal,
    closeModal,
  },
)
class DisbursalEntity extends Component {
  state = {
    isModalOpen: false,
  };

  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOAN_FUND_DISBURSED);
  }

  toggleModal = (visibility) => {
    this.setState({
      isModalOpen: visibility,
    });
  };

  render() {
    const { credit_offer_details, accepted_offer_details, disbursal_details, lender_details } =
      this.props.loanApplicationDetails;

    if (
      credit_offer_details.loading ||
      accepted_offer_details.loading ||
      lender_details.loading ||
      disbursal_details.loading
    )
      return <FormLoader />;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    );

    return (
      <div class="credit-offer-container">
        <Modal
          isOpen={this.state.isModalOpen}
          class="Modal Modal--small Modal--confirm"
          contentLabel="ConfirmModal"
          ariaHideApp={false}
        >
          <RepaymentModal creditOffer={creditOffer} closeModal={() => this.toggleModal(false)} />
        </Modal>
        <div className="loan-offer-wrapper">
          <CreditOffer
            offerDetails={creditOffer}
            approved={true}
            product={CAPITAL_PRODUCT_CODES.LOAN}
            isDisbursal={true}
            highlightCreditAmount={false}
            disbursedAmount={disbursal_details.data.disbursal.disbursed_amount}
            showInstallmentDetails={false}
            _fromWhere="Disbursal Details"
          />
          <div class="m-b">
            <SettlementAccountDetails
              user={this.props.user}
              showFinancerDetails={true}
              financer={lender_details.data.lender.name}
              _fromWhere="Disbursal Details"
            />
          </div>
          <RepaymentInformation
            creditOffer={creditOffer}
            _fromWhere="Disbursal Details"
            product={CAPITAL_PRODUCT_CODES.LOAN}
          />
        </div>
        <div className="loan-offer-action pull-right">
          <Button.Transparent
            onClick={() => {
              this.props._trackNavigationActions('BACK', APPLICATION_STATES.RZP_APPROVED);
              this.props.navigation.back();
            }}
          >
            <i className="i i-chevron-left" />
            Back
          </Button.Transparent>

          <NavLink className="btn btn-outline m-l" end to="/capital/loans/overview">
            Go to Repayment Dashboard <i className="i i-chevron-right" />
          </NavLink>
        </div>
      </div>
    );
  }
}

export default DisbursalEntity;

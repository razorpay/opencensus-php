import React, { Component, Fragment } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { closeModal } from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { bindActionCreators } from 'redux';
import { analyticsTrack } from 'common/utils/analytics';
import SamedayUpselling from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Upselling';
import { SAMEDAY_MODAL_LOCATIONS } from './Modals/ScheduledModal/constants';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';

class SettlementDetail extends Component {
  handleContactSupport = () => {
    const { closeModal, trackContactSupport = () => {} } = this.props;

    closeModal();
    trackContactSupport();
    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Contact Support',
      eventLabel: `Settlements`,
    });

    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };

  handleCompleteKYC = () => {
    this.props.closeModal();
  };

  isOnHold = () => {
    const { settlementAmount, transactionOnHold } = this.props;
    return settlementAmount?.no_settlement?.on_hold || transactionOnHold;
  };

  isOnTemporaryHold = () => {
    const { settlement } = this.props;
    return settlement.config?.data?.config?.features?.hold?.status;
  };

  handleSettlementGuideClick = () => {
    const { handleSettlementGuideClick } = this.props;

    handleSettlementGuideClick && handleSettlementGuideClick();
  };

  isBankAccountChanged = () => {
    return this.props.profile?.bankAccountChangeStatus;
  };

  get showUpsellingBanner() {
    const {
      user: {
        isOndemandSettlementEnabled,
        isAutomaticSettlementEnabled,
        isAutomaticSettlementRestricted,
      },
    } = this.props;

    return (
      isOndemandSettlementEnabled &&
      !isAutomaticSettlementEnabled &&
      !isAutomaticSettlementRestricted
    );
  }

  get onHoldTitle() {
    const user = this.props.user;
    const isOnHold = this.isOnHold();
    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (
        user.activation_status === 'under_review' ||
        user.activation_status === 'kyc_qualified_unactivated'
      ) {
        return 'Settlements under review';
      }
      if (!user.isSubmitted) {
        return 'Your Settlements will be processed post KYC submission';
      }
    }
    if (isOnHold) {
      return 'Settlements under review';
    }
    return 'Settlements on Temporary Hold';
  }

  get onHoldSubtitle() {
    const user = this.props.user;
    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (
        user.activation_status === 'under_review' ||
        user.activation_status === 'kyc_qualified_unactivated'
      ) {
        return 'We are reviewing your documents.';
      }
      if (!user.isSubmitted) {
        return 'Complete KYC to enable settlements for your account.';
      }
    }
    return 'Your settlements are currently under review and not getting processed.';
  }

  get onHoldSubtext() {
    const user = this.props.user;
    const isOnHold = this.isOnHold();
    const isBankAccountChanged = this.isBankAccountChanged();

    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (
        user.activation_status === 'under_review' ||
        user.activation_status === 'kyc_qualified_unactivated'
      ) {
        return (
          <>
            We have received your KYC information. The review process will take approximately 1-2
            working days <strong>post your first transaction</strong>. Post-approval, your
            settlements will be enabled. We will reach out to you on your registered email ID in
            case we require more information or documents.
          </>
        );
      }
      if (!user.isSubmitted) {
        return (
          <>
            Once you have submitted your KYC documents, our team will review and approve the same.
            Your settlements will be enabled post KYC verification. This process usually takes 1-2
            working days <strong>post your first transaction</strong>.
          </>
        );
      }
    }
    if (isOnHold) {
      return (
        <>
          Your settlements are under review because of some risk issues with your payments or with
          your Razorpay account. Please check your email for next steps and instructions or raise a
          query in the support section choosing the settlements under review option.
        </>
      );
    }
    if (isBankAccountChanged) {
      return (
        <div className="bank-account-change">
          <div className="pr-5">
            <i className="i i-info-outline" />
          </div>
          <div>
            Your request to update bank details is under review. Settlement will be retried after
            updation of bank account details.
          </div>
        </div>
      );
    }
    return (
      <>
        Your settlements have been put on hold because of some issues with your bank account. We
        will not be able to process further settlements until the bank account details are updated
        from your end.
      </>
    );
  }

  get actionButtons() {
    const user = this.props.user;
    const isOnHold = this.isOnHold();
    const isOnTemporaryHold = this.isOnTemporaryHold();
    const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
    const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;

    if (
      (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) &&
      !user.isSubmitted
    ) {
      return (
        <>
          <div className="settlement-detail-button-wrapper">
            <a
              className="btn btn-default full-width"
              href="https://razorpay.freshdesk.com/a/solutions/articles/11000092582&sa=D&ust=1594198150522000&usg=AFQjCNHDpL3kI_n5NQwp8zP8yPBj7RszJQ"
              target="_blank"
              rel="noopener noreferrer"
              role="button"
            >
              KYC Process Details
            </a>
          </div>
          <Link
            to={!isSignupWithEasyOnboarding ? activationFormUrl : ''}
            className="settlement-detail-button-wrapper"
            onClick={() => {
              if (isSignupWithEasyOnboarding) {
                analyticsTrack({
                  objectName: 'redirect to easy-dashboard CTA',
                  actionName: 'Redirect',
                  screen: 'settlements banner v2',
                  properties: {
                    'CTA Label': 'Submit details now',
                  },
                });
                redirectToEasyAfter1sec();
              }
            }}
          >
            <button
              type="button"
              className="btn btn-primary full-width"
              onClick={this.handleCompleteKYC}
            >
              Complete KYC
            </button>
          </Link>
        </>
      );
    }

    if (isOnHold) {
      return (
        <>
          <div className="settlement-detail-button-wrapper">
            <button
              type="button"
              className="btn btn-default full-width"
              onClick={this.handleContactSupport}
            >
              Contact Support
            </button>
          </div>
          <a
            className="settlement-detail-button-wrapper"
            href="https://razorpay.com/settlement"
            target="_blank"
            rel="noopener noreferrer"
          >
            <button
              type="button"
              className="btn btn-primary full-width"
              onClick={this.handleSettlementGuideClick}
            >
              Settlement Guide
            </button>
          </a>
        </>
      );
    }

    if (isOnTemporaryHold) {
      return (
        <Link
          className="flex-grow-1"
          to="/profile/update_bank_account"
          onClick={() => {
            this.props.closeModal();
          }}
        >
          <button type="button" className="btn btn-primary full-width">
            Update Bank Account Details
          </button>
        </Link>
      );
    }

    return (
      <a
        className="flex-grow-1"
        href="https://razorpay.com/settlement"
        target="_blank"
        rel="noopener noreferrer"
      >
        <button
          type="button"
          className="btn btn-primary full-width"
          onClick={this.handleSettlementGuideClick}
        >
          Settlement Guide
        </button>
      </a>
    );
  }

  render() {
    const isOnHold = this.isOnHold();
    const isOnTemporaryHold = this.isOnTemporaryHold();

    const isSettlementOnHold = isOnHold || isOnTemporaryHold;

    return (
      <div>
        <ModalHeader
          title="Settlement Details"
          onCloseClick={() => {
            this.props.closeModal();

            window.rzpAnalytics?.({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Close - Next Settlement Modal',
              eventLabel: `Settlements`,
            });

            this.props.trackSettlementClose && this.props.trackSettlementClose();
          }}
        />
        <div className="modal-body">
          <div className="settlement-details-overflow-box pb-0">
            <div className="emphzd pt-0">
              <div className="settlement-alert-warning">
                <span className="font-15 pr-5">
                  <strong>
                    {isSettlementOnHold ? (
                      <b>{this.onHoldTitle}</b>
                    ) : (
                      <Fragment>
                        <strong className="pr-5">
                          <Amount
                            value={this.props.settlementAmount.settlement_amount}
                            currency="INR"
                          />
                        </strong>
                        <span className="pr-5">will be settled by</span>
                        <Time
                          value={this.props.settlementAmount.next_settlement_time}
                          format="DD MMM YYYY, hh:mm A"
                        />
                      </Fragment>
                    )}
                  </strong>
                </span>
                <p>
                  {isSettlementOnHold ? (
                    <span>{this.onHoldSubtitle}</span>
                  ) : (
                    <Fragment>
                      The actual time taken for the settled amount to reflect in your bank account
                      depends on the bank’s processing time.
                    </Fragment>
                  )}
                </p>
              </div>
              <hr className="margin-10" />
              {isSettlementOnHold ? (
                <p className="grey">
                  <span className="grey">{this.onHoldSubtext}</span>
                </p>
              ) : (
                <p className="grey">
                  This is an estimate of the settlement amount and the actual settled amount may
                  vary based on the latest transactions in your account.
                </p>
              )}
            </div>
          </div>
          <div className="settlement-detail-actions">{this.actionButtons}</div>

          {this.showUpsellingBanner && (
            <SamedayUpselling
              trackKnowMore={this.props.trackKnowMore}
              trackSameDaySettlement={this.props.trackSameDaySettlement}
              from={SAMEDAY_MODAL_LOCATIONS.SETTLEMENTS_DETAILS}
            />
          )}
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => state;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetail);

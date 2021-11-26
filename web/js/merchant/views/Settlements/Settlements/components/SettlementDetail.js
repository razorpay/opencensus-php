import React, { Component, Fragment } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { closeModal } from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from '../../../TicketSupport/utils';
import { bindActionCreators } from 'redux';
import { getCustomURL } from 'merchant/components/DocsLink';
class SettlementDetail extends Component {
  handleContactSupport = () => {
    this.props.closeModal();

    window.rzpAnalytics({
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
    if (
      this.props.settlementAmount.next_settlement_time === null &&
      this.props.settlementAmount.no_settlement &&
      this.props.settlementAmount.no_settlement.on_hold === true
    ) {
      return true;
    } else {
      return false;
    }
  };

  get onHoldTitle() {
    const user = this.props.user;
    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (user.activation_status === 'under_review') {
        return 'Your Settlements are currently on Hold';
      }
      if (!user.isSubmitted) {
        return 'Your Settlements will be processed post KYC submission';
      }
    }
    return 'Settlements on hold';
  }

  get onHoldSubtitle() {
    const user = this.props.user;
    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (user.activation_status === 'under_review') {
        return 'We are reviewing your documents.';
      }
      if (!user.isSubmitted) {
        return 'Complete KYC to enable settlements for your account.';
      }
    }
    return 'Your settlements are currently not being processed.';
  }

  get onHoldSubtext() {
    const user = this.props.user;
    if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
      if (user.activation_status === 'under_review') {
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
    return (
      <>
        Because of some risk issues with your payments or with your razorpay account, your
        settlements have been put on hold.
      </>
    );
  }

  get actionButtons() {
    const user = this.props.user;
    const isOnHold = this.isOnHold();

    if (
      (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) &&
      !user.isSubmitted
    ) {
      return (
        <>
          <div>
            <a
              class="btn btn-default"
              href="https://razorpay.freshdesk.com/a/solutions/articles/11000092582&sa=D&ust=1594198150522000&usg=AFQjCNHDpL3kI_n5NQwp8zP8yPBj7RszJQ"
              target="_blank"
              rel="noopener noreferrer"
            >
              KYC Process Details
            </a>
          </div>

          <Link to="/activation">
            <button class="btn btn-primary" onClick={this.handleCompleteKYC}>
              Complete KYC
            </button>
          </Link>
        </>
      );
    }

    return (
      <>
        {isOnHold && (
          <div>
            <button class="btn btn-default" onClick={this.handleContactSupport}>
              Contact Support
            </button>
          </div>
        )}

        <a
          href={getCustomURL('https://razorpay.com/settlement')}
          target="_blank"
          rel="noopener noreferrer"
        >
          <button class="btn btn-primary">Settlement Guide</button>
        </a>
      </>
    );
  }

  render() {
    const isOnHold = this.isOnHold();
    const onHoldReason = this.props.settlementAmount.no_settlement;

    return (
      <div>
        <ModalHeader
          title="Settlement Details"
          onCloseClick={() => {
            this.props.closeModal();
            window.rzpAnalytics({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Close - Next Settlement Modal',
              eventLabel: `Settlements`,
            });
          }}
        />
        <div class="modal-body">
          <div class="settlement-details-overflow-box" style={{ paddingBottom: '0' }}>
            <div class="emphzd" style={{ paddingTop: 0 }}>
              <div class="settlement-alert-warning">
                <span style={{ fontWeight: 'bold', fontSize: '15px' }}>
                  {isOnHold ? (
                    <b>{this.onHoldTitle}</b>
                  ) : (
                    <Fragment>
                      <strong>
                        <Amount
                          value={this.props.settlementAmount.settlement_amount}
                          currency="INR"
                        />
                      </strong>{' '}
                      will be settled by
                      <Time
                        value={this.props.settlementAmount.next_settlement_time}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    </Fragment>
                  )}
                </span>{' '}
                <p>
                  {isOnHold ? (
                    <span>{this.onHoldSubtitle}</span>
                  ) : (
                    <Fragment>
                      The actual time taken for the settled amount to reflect in your bank account
                      depends on the bank’s processing time.
                    </Fragment>
                  )}
                </p>
              </div>
              <hr style={{ margin: '10px' }} />
              {isOnHold ? (
                <p class="grey">
                  {onHoldReason && onHoldReason.reason ? (
                    <span class="grey" style={{ opacity: '.7' }}>
                      {onHoldReason.reason}
                    </span>
                  ) : (
                    <span class="grey" style={{ opacity: '.7' }}>
                      {this.onHoldSubtext}
                    </span>
                  )}
                </p>
              ) : (
                <p class="grey" style={{ opacity: '.7' }}>
                  This is an estimate of the settlement amount and the actual settled amount may
                  vary based on the latest transactions in your account.
                </p>
              )}
            </div>
          </div>
          <div
            style={{
              display: 'flex',
              justifyContent: 'space-evenly',
              flexDirection: 'row',
              padding: '15px',
            }}
          >
            {this.actionButtons}
          </div>
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

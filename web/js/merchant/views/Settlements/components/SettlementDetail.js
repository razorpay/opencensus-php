import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { closeModal } from 'merchant_common/reducers/modals';
@connect(state => state, {
  closeModal,
})
export default class SettlementDetail extends Component {
  state = {
    showBreakUp: false,
  };

  handleContactSupport = () => {
    this.props.closeModal();

    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', [
        'merchant',
        'international-early-settlement',
      ]);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
  };

  render() {
    const isOnHold = !this.props.settlementAmount.next_settlement_time;
    const onHoldReason = this.props.settlementAmount.no_settlement;

    return (
      <div>
        <ModalHeader
          title={`Settlement Details`}
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div class="modal-body">
          <Fragment>
            <div
              class="settlement-details-overflow-box"
              style={{ paddingBottom: '0' }}
            >
              <div class="emphzd" style={{ paddingTop: 0 }}>
                <div class="settlement-alert-warning">
                  <span>
                    {isOnHold ? (
                      <b>Settlements on Hold</b>
                    ) : (
                      <Fragment>
                        <strong>
                          <Amount
                            value={
                              this.props.settlementAmount.settlement_amount
                            }
                            currency={'INR'}
                          />
                        </strong>{' '}
                        will be settled by
                        <Time
                          value={
                            this.props.settlementAmount.next_settlement_time
                          }
                          format={'DD MMM YYYY, hh:mm:ss a'}
                        />
                      </Fragment>
                    )}
                  </span>{' '}
                  <p>
                    {isOnHold ? (
                      <span>
                        Your settlements are currently not being processed.
                      </span>
                    ) : (
                      <Fragment>
                        The actual time taken for the settled amount to reflect
                        in your bank account depends on the bank’s processing
                        time.
                      </Fragment>
                    )}
                  </p>
                </div>
                <hr />
                {isOnHold ? (
                  <p class="grey">
                    {onHoldReason && onHoldReason.reason ? (
                      <span class="grey" style={{ opacity: '.7' }}>
                        {onHoldReason.reason}
                      </span>
                    ) : (
                      <span class="grey" style={{ opacity: '.7' }}>
                        Because of some risk issues with your payments or with
                        your razorpay account, Your settlements have been put on
                        hold.
                      </span>
                    )}
                  </p>
                ) : (
                  <p class="grey" style={{ opacity: '.7' }}>
                    This is an estimate of the settlement amount and the actual
                    settled amount may vary based on the latest transactions in
                    your account.
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
              {isOnHold && (
                <div>
                  <button
                    class="btn btn-default"
                    onClick={this.handleContactSupport}
                  >
                    Contact Support
                  </button>
                </div>
              )}

              <a href="http://razorpay.com/settlement" target="_blank">
                <button class="btn btn-primary">Settlement Guide</button>
              </a>
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

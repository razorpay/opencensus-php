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
            <div class="emphzd">
              <div class="settlement-alert-warning">
                <span>
                  {isOnHold ? (
                    <b>Settlements on Hold</b>
                  ) : (
                    <Fragment>
                      <strong>
                        <Amount
                          value={this.props.settlementAmount.settlement_amount}
                          currency={'INR'}
                        />
                      </strong>{' '}
                      will be settled by
                      <Time
                        value={this.props.settlementAmount.next_settlement_time}
                        format={'DD MMM YYYY, hh:mm:ss a'}
                      />
                    </Fragment>
                  )}
                </span>{' '}
                <p>
                  {isOnHold ? (
                    <span>
                      Your settlements are currently not being processed
                    </span>
                  ) : (
                    <Fragment>
                      The actual time taken to settle the money on your account
                      will be vary by bank.
                    </Fragment>
                  )}
                </p>
              </div>
              <hr />
              {isOnHold ? (
                <p class="grey">
                  {onHoldReason && onHoldReason.reason ? (
                    <>{onHoldReason.reason}</>
                  ) : (
                    <>
                      Because of some risk issues with your payments or with
                      your razorpay account, Your settlements have been put on
                      hold.
                    </>
                  )}
                </p>
              ) : (
                <p class="grey">
                  This is just a expected settlement amount that to be settled
                  till this time (Not a 100% accurate), Final amount will be
                  accounted for refunds also.
                </p>
              )}
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-evenly' }}>
              <a href="http://razorpay.com/settlement" target="_blank">
                <button style={{ marginTop: '15px' }} class="btn btn-primary">
                  Settlement Guide <i class="i i-arrow-right" />
                </button>
              </a>
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

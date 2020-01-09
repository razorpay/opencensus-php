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
                      Your settlements are currently not being processed.
                    </span>
                  ) : (
                    <Fragment>
                      The actual time taken for the settled amount to reflect in
                      your bank account depends on the bank’s processing time.
                    </Fragment>
                  )}
                </p>
              </div>
              <hr />
              {isOnHold ? (
                <p class="grey">
                  {onHoldReason && onHoldReason.reason ? (
                    <p class="grey" style={{ opacity: '.7' }}>
                      {onHoldReason.reason}
                    </p>
                  ) : (
                    <p class="grey" style={{ opacity: '.7' }}>
                      Because of some risk issues with your payments or with
                      your razorpay account, Your settlements have been put on
                      hold.
                    </p>
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
            <div
              style={{
                display: 'flex',
                justifyContent: 'space-evenly',
                flexDirection: 'row',
                paddingTop: '20px',
              }}
            >
              <a
                href="http://razorpay.com/settlement"
                target="_blank"
                style={{ width: '50%' }}
              >
                <button
                  style={{
                    marginTop: '15px',
                    width: '96%',
                    marginLeft: '1%',
                    marginRight: '1%',
                  }}
                  class="btn btn-primary"
                >
                  Settlement Guide <i class="i i-arrow-right" />
                </button>
              </a>
              {isOnHold && (
                <>
                  <div
                    style={{
                      margin: '17px 10px',
                    }}
                  >
                    <b>OR</b>
                  </div>
                  <a
                    href="https://razorpay.com/support/"
                    target="_blank"
                    style={{ width: '50%' }}
                  >
                    <button
                      class="btn btn-primary"
                      style={{
                        marginTop: '15px',
                        border: '0px',
                        width: '96%',
                        marginLeft: '1%',
                        marginRight: '1%',
                      }}
                    >
                      Contact Support
                    </button>
                  </a>
                </>
              )}
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

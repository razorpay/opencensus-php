import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchant/views/Settlements/components/Details';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';

export default class SettlementDetail extends Component {
  state = {
    showBreakUp: false,
  };
  render() {
    console.log(this.props, 'props');
    const isOnHold = !this.props.settlementAmount.next_settlement_time;
    return (
      <div>
        <ModalHeader title={`Settlement Details`} />
        <div class="modal-body">
          <Fragment>
            {!isOnHold ? (
              <div class="grey">
                Your payements get settled to your account in
              </div>
            ) : null}

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
                  Because of some risk issues with your payments or with your
                  razorpay account, Your settlements have been put on hold.
                </p>
              ) : (
                <p class="grey">
                  This is just a expected settlement amount that to be settled
                  till this time (Not a 100% accurate), Final amount will be
                  accounted for refunds also.
                </p>
              )}
            </div>
            {isOnHold ? null : (
              <div style={{ padding: '13px' }}>
                <p>
                  <b>Early Settlement:</b> Get your settlements on the same day
                  from now on automtically !
                </p>
                <button style={{ marginTop: '15px' }} class="btn btn-primary">
                  Enable Now <i class="i i-arrow-right" />
                </button>
              </div>
            )}
          </Fragment>
        </div>
      </div>
    );
  }
}

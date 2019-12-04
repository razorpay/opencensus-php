import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchant/views/Settlements/components/Details';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';

export default class SettlementDetail extends Component {
  state = {
    showBreakUp: false,
  };
  render() {
    return (
      <div>
        <ModalHeader title={`Settlement Details`} />
        <div class="modal-body">
          <Fragment>
            <div class="grey">
              Your payements get settled to your account in
            </div>
            <div class="emphzd">
              <div class="settlement-alert-warning">
                <span>
                  <strong>₹3,24,666.36</strong> will be settled by 01 Dec, 5PM.
                </span>{' '}
                <br />
                <button
                  onClick={() => {
                    const showBreakUp = !this.state.showBreakUp;
                    this.setState({ showBreakUp });
                  }}
                  style={{ marginTop: '10px' }}
                  class="outline btn-primary"
                >
                  Show Breakup{' '}
                  <i
                    class={`i i-arrow-${
                      this.state.showBreakUp ? 'up' : 'down'
                    }`}
                  />
                </button>
              </div>
              {this.state.showBreakUp ? (
                <div class="breakup">
                  <div class="flex grey">
                    <div style={{ width: '60%' }}>Total Amount</div>
                    <div style={{ width: '40%' }}>
                      <span>
                        <Amount value={326618} currency={'INR'} />
                      </span>
                    </div>
                  </div>
                  <div class="flex grey">
                    <div style={{ width: '60%' }}>Fees (0.2%)</div>
                    <div style={{ width: '40%' }}>
                      <span>
                        <Amount value={-1425} currency={'INR'} />
                      </span>
                    </div>
                  </div>
                  <div class="flex grey">
                    <div style={{ width: '60%' }}>Taxes</div>
                    <div style={{ width: '40%' }}>
                      <span>
                        <Amount value={-455} currency={'INR'} />
                      </span>
                    </div>
                  </div>
                  <div class="flex grey">
                    <div style={{ width: '60%' }}>Amount to be settled</div>
                    <div style={{ width: '40%' }}>
                      <span>
                        <Amount value={324193} currency={'INR'} />
                      </span>
                    </div>
                  </div>
                </div>
              ) : null}
              <hr />
              <p class="grey">
                This is just a expected settlement amount that to be settled
                till this time (Not a 100% accurate), Final amount will be
                accounted for refunds also.
              </p>
            </div>
            <div style={{ padding: '13px' }}>
              <p>
                <b>Early Settlement:</b> Get your settlements on the same day
                from now on automtically !
              </p>
              <button style={{ marginTop: '15px' }} class="btn btn-primary">
                Enable Now <i class="i i-arrow-right" />
              </button>
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

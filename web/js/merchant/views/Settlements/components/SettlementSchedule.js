import React, { Component, Fragment } from 'react';
import SettlementsExample from 'merchant/views/Settlements/components/SettlementsExample';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import { closeModal } from 'merchant_common/reducers/modals';

export default class SettlementSchedule extends Component {
  state = {
    showBreakUp: false,
    showExample: false,
  };
  render() {
    return (
      <div>
        <ModalHeader
          title={`Settlement Schedule`}
          onCloseClick={() => closeModal()}
        />
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
                Because of bank holiday [Holiday Name] on 04 Dec 2019, the next
                settlement would happen on Wednesday.{' '}
              </p>
            </div>
            <div style={{ padding: '13px' }}>
              <p>
                <b>Note:</b> Bank Holidays aren’t counted as working days.{' '}
                <br />
                <button
                  style={{ marginTop: '10px' }}
                  onClick={() => {
                    const { showExample } = this.state;
                    this.setState({ showExample: !showExample });
                  }}
                  class="btn-outline"
                >
                  {this.state.showExample ? 'Hide' : 'Show'} Example{' '}
                  <i
                    class={`i i-arrow-${
                      this.state.showExample ? 'up' : 'down'
                    }`}
                  />
                </button>
              </p>
              {this.state.showExample ? (
                <Fragment>
                  <div
                    class="box"
                    style={{
                      marginTop: '18px',
                      border: '1px solid #E2E2E2',
                      borderRadius: '2px',
                    }}
                  >
                    <div class="box-heading">
                      <h5 style={{ textAlign: 'left' }}>
                        <b>Example: No Bank Holiday</b>
                      </h5>
                      <SettlementsExample duration={6} />
                    </div>
                  </div>
                  <div
                    class="box"
                    style={{
                      marginTop: '18px',
                      border: '1px solid #E2E2E2',
                      borderRadius: '2px',
                    }}
                  >
                    <div class="box-heading">
                      <h5 style={{ textAlign: 'left' }}>
                        <b>Example: Bank Holiday in between</b>
                      </h5>
                      <SettlementsExample holiday={true} duration={6} />
                    </div>
                  </div>
                </Fragment>
              ) : null}

              <div style={{ marginTop: '15px' }}>
                <button
                  style={{ width: '48%', margin: '5px' }}
                  class="btn btn-outline"
                >
                  List of Bank Holidays
                </button>
                <button
                  style={{ width: '48%', margin: '5px' }}
                  class="btn btn-primary"
                >
                  Settlement Guide
                </button>
              </div>
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

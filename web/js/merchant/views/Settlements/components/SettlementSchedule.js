import React, { Component, Fragment } from 'react';
import SettlementsExample from 'merchant/views/Settlements/components/SettlementsExample';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';

@connect(state => state, {
  closeModal,
})
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
          onCloseClick={() => this.props.closeModal()}
        />
        <div class="modal-body">
          <Fragment>
            <div class="grey">Your payments get settled to your account in</div>
            <div class="emphzd">
              <div class="emphzd-div">
                <div class="flex">
                  <div class="w50">Domestic Payments</div>
                  <div class="w50">T+3 working days</div>
                </div>
                <div class="flex">
                  <div class="w50">International Payments</div>
                  <div class="w50">T+7 working days</div>
                </div>
              </div>
              <div style={{ margin: '10px' }}>
                <p class="grey">Other method specific Settlement schedules,</p>
                <div class="flex" style={{ margin: '10px', fontSize: '16px' }}>
                  <div class="w50">Method 1</div>
                  <div class="w50">
                    <b>T+4</b> Working Days
                  </div>
                </div>
                <div class="flex" style={{ margin: '10px', fontSize: '16px' }}>
                  <div class="w50">Method 2</div>
                  <div class="w50">
                    <b>T+6</b> Working Days
                  </div>
                </div>
              </div>
              <div>
                <div
                  class="flex grey"
                  style={{ margin: '10px', fontSize: '13px' }}
                >
                  <div class="w50">
                    <span class="text-danger">*</span> for Default Schedules
                  </div>
                  <div class="w50">(T is the date of payment capture)</div>
                </div>
              </div>
              <hr />
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
                      <SettlementsExample duration={4} />
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
                      <SettlementsExample holiday={true} duration={4} />
                    </div>
                  </div>
                </Fragment>
              ) : null}

              <div style={{ marginTop: '15px' }}>
                <button style={{ width: '48%' }} class="btn btn-outline">
                  List of Bank Holidays
                </button>
                <a href="http://razorpay.com/settlement" target="_blank">
                  <button
                    style={{ width: '48%', marginLeft: '5px' }}
                    class="btn btn-primary"
                  >
                    Settlement Guide
                  </button>
                </a>
              </div>
            </div>
          </Fragment>
        </div>
      </div>
    );
  }
}

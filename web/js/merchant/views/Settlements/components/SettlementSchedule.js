import React, { Component, Fragment } from 'react';
import SettlementsExample from 'merchant/views/Settlements/components/SettlementsExample';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import HolidayModal from 'merchant/views/Settlements/components/Modals/HolidayModal';

@connect(state => state.settlement, {
  closeModal,
  openModal,
})
export default class SettlementSchedule extends Component {
  state = {
    showBreakUp: false,
    showExample: false,
    defaultDomestic: [],
    defaultInternational: [],
    otherMethods: [],
  };

  componentDidMount() {
    this.processSchedule();
  }

  processSchedule = () => {
    const { schedule } = this.props;

    let defaultDomestic = schedule.data.filter(
      item => item.method === null && item.international === 0
    );
    let defaultInternational = schedule.data.filter(
      item => item.method === null && item.international === 1
    );
    let otherMethods = schedule.data.filter(item => item.method !== null);

    this.setState({
      defaultDomestic,
      defaultInternational,
      otherMethods,
    });
  };

  render() {
    console.log(this.props);
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
                {this.state.defaultDomestic.length > 0 && (
                  <div class="flex">
                    <div class="w50">Domestic Payments</div>
                    <div class="w50">
                      T+{this.state.defaultDomestic[0].delay} working days
                    </div>
                  </div>
                )}
                {this.state.defaultInternational.length > 0 && (
                  <div class="flex">
                    <div class="w50">International Payments</div>
                    <div class="w50">
                      T+{this.state.defaultInternational[0].delay} working days
                    </div>
                  </div>
                )}
              </div>
              {this.state.otherMethods.map((item, idx) => {
                <div style={{ margin: '10px' }} key={idx}>
                  <p class="grey">
                    Other method specific Settlement schedules,
                  </p>
                  <div
                    class="flex"
                    style={{ margin: '10px', fontSize: '16px' }}
                  >
                    <div class="w50">{item.method}</div>
                    <div class="w50">
                      <b>T+{item.delay}</b> Working Days
                    </div>
                  </div>
                </div>;
              })}
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
                <button
                  onClick={() => {
                    this.props.openModal({
                      size: 'small',
                      component: <HolidayModal data={this.props.holidayList} />,
                    });
                  }}
                  style={{ width: '48%' }}
                  class="btn btn-outline"
                >
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

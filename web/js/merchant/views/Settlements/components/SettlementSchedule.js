import React, { Component, Fragment } from 'react';
import SettlementsExample from 'merchant/views/Settlements/components/SettlementsExample';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import HolidayModal from 'merchant/views/Settlements/components/Modals/HolidayModal';
import ContentToggler from 'common/ui/Toggler/ContentToggler';

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

  formatTime = hrs => {
    const formattedHrs = hrs.map((hr, idx) => {
      return moment(hr, 'hh').format('LT');
    });

    return formattedHrs.map((hr, idx) => {
      if (idx === formattedHrs.length - 1) {
        return <Fragment key={idx}>{`${hr} Daily `}</Fragment>;
      } else {
        return <Fragment key={idx}>{`${hr}, `}</Fragment>;
      }
    });
  };

  toggleExample = () => {
    this.setState(prevState => {
      return {
        showExample: !prevState.showExample,
      };
    });

    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlement UI Revamp',
      eventAction: 'View Settlement Example',
    });
  };

  viewHolidayList = () => {
    if (this.props.holidayList.error === true) return;

    this.props.openModal({
      size: 'small',
      component: <HolidayModal data={this.props.holidayList} />,
    });

    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlement UI Revamp',
      eventAction: 'View Holiday List',
    });
  };

  render() {
    return (
      <div>
        <ModalHeader
          title={`Settlement Cycle`}
          onCloseClick={() => this.props.closeModal()}
        />
        <div class="modal-body">
          <Fragment>
            <div class="settlement-details-overflow-box">
              <div
                style={{
                  textAlign: 'left',
                  paddingBottom: '10px',
                  marginLeft: '15px',
                }}
              >
                Your payments get settled to your account in
              </div>
              <div class="emphzd">
                <div class="emphzd-div">
                  {this.state.defaultDomestic.length > 0 && (
                    <div class="flex">
                      <div class="w50 text-left">
                        Domestic Payments<span class="text-danger">*</span>
                      </div>
                      <div class="w50 text-right">
                        {this.state.defaultDomestic[0]
                          .is_early_settlement_schedule ? (
                          this.formatTime(this.state.defaultDomestic[0].hour)
                        ) : (
                          <>
                            <strong>
                              T+{this.state.defaultDomestic[0].delay}
                            </strong>{' '}
                            working days
                          </>
                        )}
                      </div>
                    </div>
                  )}

                  {this.state.defaultInternational.length > 0 && (
                    <div class="flex">
                      <div class="w50 text-left">
                        International Payments<span class="text-danger">*</span>
                      </div>
                      <div class="w50 text-right">
                        {this.state.defaultInternational[0]
                          .is_early_settlement_schedule ? (
                          this.formatTime(
                            this.state.defaultInternational[0].hour
                          )
                        ) : (
                          <>
                            <strong>
                              T+{this.state.defaultInternational[0].delay}
                            </strong>{' '}
                            working days
                          </>
                        )}
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
                      <div class="w50 text-left p20">{item.method}</div>
                      <div class="w50 text-right p20">
                        {item.is_early_settlement_schedule ? (
                          this.formatTime(item.hour)
                        ) : (
                          <>
                            <strong>T+{item.delay}</strong> working days
                          </>
                        )}
                      </div>
                    </div>
                  </div>;
                })}
                <div
                  class="settlement-default-note"
                  style={{ fontSize: '13px' }}
                >
                  <div class="w50 text-left">
                    <span class="text-danger">*</span> for Default Schedules
                  </div>
                  <div class="w50 text-right">
                    (T is the date of payment capture)
                  </div>
                </div>
                <hr style={{ marginTop: 0 }} />
              </div>
            </div>{' '}
            <div style={{ padding: '13px' }}>
              <p>
                <b>Note:</b> Weekends aren’t counted as working days. <br />
                <a
                  style={{ marginTop: '10px' }}
                  onClick={this.toggleExample}
                  class="link"
                >
                  {this.state.showExample ? 'Hide' : 'View'} Examples{' '}
                  <i
                    class={`i i-arrow-${
                      this.state.showExample ? 'up' : 'down'
                    }`}
                  />
                </a>
              </p>
              {this.state.showExample ? (
                <Fragment>
                  <h5>Following is an example for T+4 Days</h5>

                  <div class="box settlement-holiday-example">
                    <div class="box-heading">
                      {/* <h5 style={{ textAlign: 'left' }}>
                        <b>Example: No Bank Holiday</b>
                      </h5> */}
                      <img src="img/settlement-example/full.svg" />
                    </div>
                  </div>
                </Fragment>
              ) : null}

              <div style={{ marginTop: '15px' }}>
                <button
                  onClick={this.viewHolidayList}
                  style={{ width: '48%', margin: '0 1%' }}
                  class="btn btn-outline"
                >
                  List of Bank Holidays
                </button>
                <a href="http://razorpay.com/settlement" target="_blank">
                  <button
                    style={{ width: '48%', margin: '0 1%' }}
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

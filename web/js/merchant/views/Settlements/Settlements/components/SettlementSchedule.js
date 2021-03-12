import React, { Component, Fragment } from 'react';
import SettlementsExample from 'merchant/views/Settlements/Settlements/components/SettlementsExample';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

@connect((state) => state.settlement, {
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
      (item) => item.method === null && item.international === 0,
    );
    let defaultInternational = schedule.data.filter(
      (item) => item.method === null && item.international === 1,
    );
    let otherMethods = schedule.data.filter((item) => item.method !== null);

    this.setState({
      defaultDomestic,
      defaultInternational,
      otherMethods,
    });
  };

  formatTime = (hrs) => {
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
    this.setState((prevState) => {
      return {
        showExample: !prevState.showExample,
      };
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: this.state.showExample ? 'View Examples' : 'Hide Examples',
      eventLabel: `View settlement Cycle`,
    });
  };

  viewHolidayList = () => {
    analyticsTrack({
      objectName: 'view settlement schedule popup',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        action: 'bank holidays',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (this.props.holidayList.error === true) return;

    this.props.openModal({
      size: 'small',
      component: <HolidayModal data={this.props.holidayList} />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'List of Bank Holidays',
      eventLabel: `View settlement Cycle`,
    });
  };

  render() {
    return (
      <div>
        <ModalHeader
          title={`Settlement Cycle`}
          onCloseClick={() => {
            analyticsTrack({
              objectName: 'view settlement schedule popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                action: 'cancel',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
            window.rzpAnalytics({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Close',
              eventLabel: `Settlment Cycle`,
            });
          }}
        />
        <div class="modal-body">
          <Fragment>
            <div class="settlement-details-overflow-box">
              <div class="payment-heading">Your payments get settled to your account in,</div>
              <div class="emphzd" style={{ paddingBottom: 0 }}>
                <div class="emphzd-div">
                  {this.state.defaultDomestic.length > 0 && (
                    <div class="flex">
                      <div class="w50 text-left">
                        Domestic Payments<span class="text-danger">*</span>
                      </div>
                      <div class="w50 text-right">
                        {this.state.defaultDomestic[0].is_early_settlement_schedule ? (
                          this.formatTime(this.state.defaultDomestic[0].hour)
                        ) : (
                          <>
                            <strong>T+{this.state.defaultDomestic[0].delay}</strong> working days
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
                        {this.state.defaultInternational[0].is_early_settlement_schedule ? (
                          this.formatTime(this.state.defaultInternational[0].hour)
                        ) : (
                          <>
                            <strong>T+{this.state.defaultInternational[0].delay}</strong> working
                            days
                          </>
                        )}
                      </div>
                    </div>
                  )}
                </div>
                {this.state.otherMethods.length ? (
                  <p class="grey" style={{ margin: '10px' }}>
                    Other method specific Settlement schedules,
                  </p>
                ) : null}

                {this.state.otherMethods.map((item, idx) => {
                  return (
                    <div style={{ margin: '10px' }} key={idx}>
                      <div class="flex" style={{ fontSize: '16px' }}>
                        <div class="w50 text-left">
                          {titleCase(item.method)}
                          {' ('}
                          {item.international ? 'International' : 'Domestic'}
                          {')'}
                        </div>
                        <div class="w50 text-right">
                          {item.is_early_settlement_schedule ? (
                            this.formatTime(item.hour)
                          ) : (
                            <>
                              <strong>T+{item.delay}</strong> working days
                            </>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })}
                <div class="settlement-default-note" style={{ fontSize: '13px' }}>
                  <div class="w50 text-left">
                    <span class="text-danger">*</span> for Default Schedules
                  </div>
                  <div class="w50 text-right">(T is the date of payment capture)</div>
                </div>
              </div>
            </div>{' '}
            <div style={{ padding: '13px' }}>
              <p>
                <b>Note:</b> Weekends aren’t counted as working days. <br />
                <a onClick={this.toggleExample} class="link">
                  {this.state.showExample ? 'Hide' : 'View'} Examples{' '}
                  <i class={`i i-arrow-${this.state.showExample ? 'up' : 'down'}`} />
                </a>
              </p>
              {this.state.showExample ? (
                <Fragment>
                  <h5>Following is an example for T+2 Days</h5>
                  <img src="/img/settlement-example.svg" style={{ width: '100%' }} />
                </Fragment>
              ) : null}

              <div style={{ marginTop: '25px' }}>
                <button
                  onClick={this.viewHolidayList}
                  style={{ width: '48%', margin: '0 1%' }}
                  class="btn btn-outline"
                >
                  Bank Holidays
                </button>
                <a href="https://razorpay.com/settlement" target="_blank">
                  <button
                    style={{ width: '48%', margin: '0 1%' }}
                    class="btn btn-primary"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'view settlement schedule popup',
                        actionName: 'clicked',
                        screen: 'my account',
                        properties: {
                          action: 'settlement guide',
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      window.rzpAnalytics({
                        eventCategory: 'Settlement Revamp',
                        eventAction: 'Settlement Guide',
                        eventLabel: `View settlement Cycle`,
                      });
                    }}
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

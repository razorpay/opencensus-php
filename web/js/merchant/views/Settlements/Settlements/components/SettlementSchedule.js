import React, { Component, Fragment } from 'react';
import moment from 'moment';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { bindActionCreators } from 'redux';
import { getCustomURL } from 'merchant/components/DocsLink';
class SettlementSchedule extends Component {
  state = {
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

    const defaultDomestic = schedule.data.filter(
      (item) => item.method === null && item.international === 0,
    );
    const defaultInternational = schedule.data.filter(
      (item) => item.method === null && item.international === 1,
    );
    const otherMethods = schedule.data.filter((item) => item.method !== null);

    this.setState({
      defaultDomestic,
      defaultInternational,
      otherMethods,
    });
  };

  formatTime = (hrs) => {
    const formattedHrs = hrs.map((hr) => {
      // eslint-disable-next-line no-undef
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

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: this.state.showExample ? 'View Examples' : 'Hide Examples',
      eventLabel: `View settlement Cycle`,
    });
  };
  analyticsObjectName = () => {
    if (this.props.location === 'settlements') {
      return 'settlement cycle popup';
    } else {
      return 'view settlement schedule popup';
    }
  };

  viewHolidayList = () => {
    analyticsTrack({
      objectName: this.analyticsObjectName(),
      actionName: 'clicked',
      screen: `${this.props.location}`,
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

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'List of Bank Holidays',
      eventLabel: `View settlement Cycle`,
    });
  };

  render() {
    return (
      <div>
        <ModalHeader
          title="Settlement Cycle"
          onCloseClick={() => {
            analyticsTrack({
              objectName: this.analyticsObjectName(),
              actionName: 'clicked',
              screen: `${this.props.location}`,
              properties: {
                action: 'cancel',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
            window.rzpAnalytics?.({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Close',
              eventLabel: `Settlment Cycle`,
            });
          }}
        />
        <div class="modal-body">
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
                          <strong>T+{this.state.defaultInternational[0].delay}</strong> working days
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

            <div class="settlement-btn-container">
              <div>
                <button onClick={this.viewHolidayList} class="btn btn-default">
                  Bank Holidays
                </button>
              </div>
              <div>
                <a
                  href={getCustomURL('https://razorpay.com/settlement')}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <button
                    class="btn btn-primary"
                    onClick={() => {
                      analyticsTrack({
                        objectName: this.analyticsObjectName(),
                        actionName: 'clicked',
                        screen: `${this.props.location}`,
                        properties: {
                          action: 'settlement guide',
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      window.rzpAnalytics?.({
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
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => state.settlement;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal, openModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementSchedule);

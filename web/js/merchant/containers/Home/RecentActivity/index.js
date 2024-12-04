/* eslint-disable react/no-unsafe */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { compose } from 'redux';

import Button from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import GenericPanel, {
  PanelBody,
  PanelTopbar,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { fetchPayments, fetchRefunds, fetchSettlements } from 'merchant/reducers/collection';
import { openModal } from 'merchant_common/reducers/modals';

import { tabs, tabsMeta } from './data';
import { trackTabClick, trackEntityClick, trackGoToLinks, selfServeTracking } from './ga';
import { withI18Service } from 'common/i18';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';

const shouldDisplayCompact = (windowWidth) => {
  return windowWidth < 480;
};

const DEFAULT_PARAMS = { count: 5 };

const Row = ({ record, tabName, tabTitle, sectionTitle, displayCompact, settlementCurrency }) => {
  const tabMeta = tabsMeta[tabName];

  return (
    <tr>
      {tabMeta.columns.map((columnMeta, index) => {
        if (displayCompact && index === 1) {
          return null;
        }

        let value = record[columnMeta.recordKey];

        value =
          typeof columnMeta.transfomer === 'function'
            ? columnMeta.transfomer(value, record, tabName, displayCompact, settlementCurrency)
            : value;

        if (columnMeta.recordKey === 'id') {
          value = React.cloneElement(value, {
            onClick: () => {
              trackEntityClick(tabTitle, sectionTitle);
              const selfServeInitiateData = {
                screen: 'Home',
                page: 'Recentactivity',
                props: {
                  initiatePoint: `${tabName}-table`,
                },
              };
              if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;

              switch (tabName) {
                case 'payments': {
                  selfServeInitiateData.selfServeAction = 'Payment Details Fetched';
                  break;
                }
                case 'settlements': {
                  selfServeInitiateData.selfServeAction = 'Settlement Details Fetched';
                  break;
                }
                case 'refunds': {
                  selfServeInitiateData.selfServeAction = 'Refund Details Fetched';
                  break;
                }
                default: {
                  selfServeInitiateData.selfServeAction = '';
                }
              }

              if (selfServeInitiateData.selfServeAction !== '') {
                selfServeTrackInitiate(selfServeInitiateData);
              } else {
                selfServeTracking(tabName);
              }
            },
          });
        }

        return <td key={`${tabName}-${index}`}>{value}</td>;
      })}
    </tr>
  );
};

class RecentActivity extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedTab: tabs[0],
      displayCompact: shouldDisplayCompact(props.windowWidth),
    };
  }

  handleTabClick = (e) => {
    e.preventDefault();

    const tabName = e.target.getAttribute('name');
    analyticsTrack({
      objectName: 'recent activity',
      actionName: 'viewed',
      screen: 'home page',
      properties: {
        tabName,
        location: 'recent activity',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.setState({ selectedTab: tabName });
    trackTabClick(titleCase(tabName), this.props.sectionTitle);
  };

  handleResize(props = this.props) {
    this.setState({
      displayCompact: shouldDisplayCompact(props.windowWidth),
    });
  }

  fetchData(params) {
    const { startDate, endDate, fetchRefunds, fetchSettlements } = this.props;
    const payload = { ...params };
    if (startDate && endDate) {
      payload.from = startDate.unix();
      payload.to = endDate.unix();
    }

    this.fetchPayments(params, this.props);
    fetchRefunds(payload);
    fetchSettlements(payload);
  }

  fetchPayments(params, nextProps) {
    const payload = { ...params };

    if (nextProps.startDate && nextProps.endDate) {
      payload.from = nextProps.startDate.unix();
      payload.to = nextProps.endDate.unix();
    }

    this.props.fetchPayments(payload).then((data) => {
      return this.props.onFetchPayments && this.props.onFetchPayments(data && data.data);
    });
  }

  componentDidMount() {
    this.fetchData(DEFAULT_PARAMS);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.windowWidth !== nextProps.windowWidth) {
      this.handleResize(nextProps);
    }

    // On date range change, fetch payments for the given range
    if (
      this.props.startDate?.unix() !== nextProps.startDate?.unix() ||
      this.props.endDate?.unix() !== nextProps.endDate?.unix()
    ) {
      const payload = { ...DEFAULT_PARAMS };
      const { fetchRefunds, fetchSettlements } = this.props;
      if (nextProps.startDate && nextProps.endDate) {
        payload.from = nextProps.startDate.unix();
        payload.to = nextProps.endDate.unix();
      }
      this.fetchPayments(DEFAULT_PARAMS, nextProps);
      fetchRefunds(payload);
      fetchSettlements(payload);
    }
  }

  enableInstantRefunds = () => {
    selfServeTrackInitiate({
      selfServeAction: 'Enable Instant Refund Page View',
      page: 'Home',
      screen: 'Home',
    });
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Enable Now',
      eventLabel: `Recent Activity | Enable Now`,
    });
    analyticsTrack({
      objectName: 'instant refund',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'recent activity',
      },
    });
    selfServeTrackSuccess({
      selfServeAction: 'Enable Instant Refund Page View',
      page: 'Home',
      screen: 'Home',
    });
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`Click - Enable Now`, {
        label: 'Recent Activity',
        session_id: window.session_id,
        category: 'Merchant Dashboard - IR',
      }),
    );
  };

  render() {
    const { selectedTab, displayCompact } = this.state;
    const selectedTabData = this.props[selectedTab];
    const numColumns = tabsMeta[selectedTab].columns.length;
    const selectedTabTitle = titleCase(selectedTab);
    let body = null;
    const settlementCurrency = this.props.settlementCurrency;

    if (selectedTabData.loading || selectedTabData.items.length === 0) {
      let noRecordsFound = `No ${selectedTab} found.`;
      if (selectedTab === 'payments') {
        noRecordsFound = 'No payments found for the selected duration.';
      }

      body = (
        <tr>
          <td colSpan={numColumns}>
            <center>{selectedTabData.loading ? 'Please Wait...' : noRecordsFound}</center>
          </td>
        </tr>
      );
    } else {
      body = selectedTabData.items.map((record, index) => {
        return (
          <Row
            key={index}
            record={record}
            tabName={selectedTab}
            tabTitle={selectedTabTitle}
            sectionTitle={this.props.sectionTitle}
            displayCompact={displayCompact}
            settlementCurrency={settlementCurrency}
          />
        );
      });
    }

    const isJkOrg = isJKOfflineMerchant(this.props.org, this.props.user);
    const newTabs = tabs.filter((tabName) => {
      if (tabName === 'refunds') {
        return !this.props.i18.isConfigTagEnabled('refunds.refund') && !isJkOrg;
      }

      if (tabName === 'settlements') {
        return !this.props.i18.isConfigTagEnabled('settlements.settlement') && !isJkOrg;
      }

      return true;
    });

    return (
      <GenericPanel
        className={`recent-activity-cont${displayCompact ? ' compact' : ''}`}
        isLoading={selectedTabData.loading}
      >
        <PanelTopbar>
          <tabbed-container>
            <div className="row">
              {newTabs.map((tabName, index) => {
                const className = `${tabName === selectedTab ? 'active ' : ''}col-xs-4`;

                return (
                  <a className={className} key={index} name={tabName} onClick={this.handleTabClick}>
                    {tabName.toUpperCase()}
                  </a>
                );
              })}
            </div>
          </tabbed-container>
        </PanelTopbar>
        <PanelBody>
          <table className="table table-striped">
            <tbody>{body}</tbody>
          </table>
        </PanelBody>
        <PanelFooter>
          <div className="clearfix">
            {selectedTab.toLowerCase() === 'settlements' &&
            this.props.user.isOndemandSettlementEnabled &&
            this.props.currentBalance.data.balance >= 100 ? (
              <React.Fragment>
                <span>
                  <i className="i i-early-settlement" />
                  <span className="early-stl-label">You are eligible for instant settlements</span>
                </span>
                <Button.Secondary class="settle-btn-act" onClick={this.props.onSelect}>
                  Settle Now
                </Button.Secondary>
              </React.Fragment>
            ) : (
              <></>
            )}
            {selectedTabTitle === 'Refunds' &&
            !showWhenUtil({
              featureEnabled: 'disable_instant_refunds',
            }) &&
            this.props.default_refund_speed == 'normal' ? (
              <div class="pull-left main-page-process-instantly">
                <p>
                  <i class="i i-instant-refund" /> Process all refunds instantly
                  <Link to="/config#instantrefunds">
                    <button onClick={this.enableInstantRefunds} class="btn btn-outline">
                      <b>Enable Now</b>
                    </button>
                  </Link>
                </p>
              </div>
            ) : null}
            <div className="pull-right">
              <Link
                target={isJkOrg ? '_self' : '_blank'}
                rel="noreferrer noopener"
                to={`/${selectedTab}`}
                onClick={() => {
                  analyticsTrack({
                    objectName: selectedTab,
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      tabName: selectedTab,
                      location: 'recent activity',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  return trackGoToLinks(selectedTabTitle, this.props.sectionTitle);
                }}
              >
                View all {selectedTabTitle} <i className="i i-chevron-right" />
              </Link>
            </div>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    payments: state.payments,
    default_refund_speed: state.config.config.default_refund_speed,
    refunds: state.refunds,
    settlements: state.settlements,
    windowWidth: state.app.windowWidth,
    user: state.session.user,
    org: state.session.org,
    config: state.config.config,
  };
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking({
    page: 'ScheduledNitroBanner',
  }),
  connect(mapStateToProps, {
    fetchPayments,
    openModal,
    fetchRefunds,
    fetchSettlements,
  }),
)(withI18Service(RecentActivity));

import React, { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Link } from 'react-router-dom';
import Button from 'common/new-ui/Button';
import { openModal } from 'merchant_common/reducers/modals';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { fetchPayments, fetchRefunds, fetchSettlements } from 'merchant/reducers/collection';

import GenericPanel, {
  PanelBody,
  PanelTopbar,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { tabs, tabsMeta } from './data';

import { trackTabClick, trackEntityClick, trackGoToLinks } from './ga';

const shouldDisplayCompact = (windowWidth) => {
  return windowWidth < 480;
};

const DEFAULT_PARAMS = { count: 5 };

const Row = ({ record, tabName, tabTitle, sectionTitle, displayCompact }) => {
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
            ? columnMeta.transfomer(value, record, tabName, displayCompact)
            : value;

        if (columnMeta.recordKey === 'id') {
          value = (
            <value.type {...value.props} onClick={() => trackEntityClick(tabTitle, sectionTitle)}>
              {value.props.children}
            </value.type>
          );
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
    this.fetchPayments(params, this.props);
    this.props.fetchRefunds(params);
    this.props.fetchSettlements(params);
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

  componentWillMount() {
    this.fetchData(DEFAULT_PARAMS);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.windowWidth !== nextProps.windowWidth) {
      this.handleResize(nextProps);
    }

    // On date range change, fetch payments for the given range
    if (
      this.props.startDate?.unix() !== nextProps.startDate?.unix() ||
      this.props.endDate?.unix() !== nextProps.endDate?.unix()
    ) {
      this.fetchPayments(DEFAULT_PARAMS, nextProps);
    }
  }

  enableInstantRefunds = () => {
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
          />
        );
      });
    }

    return (
      <GenericPanel
        className={`recent-activity-cont${displayCompact ? ' compact' : ''}`}
        isLoading={selectedTabData.loading}
      >
        <PanelTopbar>
          <tabbed-container>
            <div className="row">
              {tabs.map((tabName, index) => {
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
                target="_blank"
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
)(RecentActivity);

import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { compose } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Breadcrumb, { BreadcrumbItem } from 'common/ui/Breadcrumb';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
  PanelFallback,
} from 'merchant/components/Home/GenericPanel';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import { API_ERROR, API_INVALID_RESP } from 'merchant/components/Home/data';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import MoreOptionsButton from 'merchant/containers/Home/MoreOptionsButton';
import { trackGoToLinks, trackNoData, trackError } from 'merchant/containers/Home/ga';
import D3ScriptLoaderHoc from 'merchant/hoc/D3ScriptLoaderHoc';
import { fetch } from 'merchant/reducers/pokedex';
import lazy from 'merchant/routes/LazyLoader';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Mobile from './Mobile';
import { getQuery, aggTypes } from './data';
import { trackBreadcrumbClick } from './ga';
const Treemap = lazy(() =>
  import(/* webpackChunkName: 'Treemap' */ 'merchant/containers/Home/PaymentMethods/Treemap'),
);

function getLevels(hierarchy, levels = []) {
  if (hierarchy.parent) {
    getLevels(hierarchy.parent, levels);
  }

  levels.push({
    name: hierarchy.displayText,
    data: hierarchy,
  });

  return levels;
}

const csvDateFormat = 'DD-MM-YYYY';
const mobileAggKey = 'method';

class PaymentMethods extends Component {
  constructor(props) {
    super(props);

    this.state = {
      data: null,
      levels: [],
      currentLevel: null,
      csvData: null,
      isLoading: false,
      error: '',
      hierarchy: { values: [] },
      selectedAgg: aggTypes[0],
    };

    this.requestId = 0;

    this.onLevelChange = this.onLevelChange.bind(this);
    this.onCSVData = this.onCSVData.bind(this);
    this.openReportModal = this.openReportModal.bind(this);
    this.onAggChange = this.onAggChange.bind(this);
  }

  fetchData(startDate, endDate, aggType) {
    this.setState({
      isLoading: true,
      error: '',
    });

    const { selectedAgg } = this.state;
    const { sectionTitle, analyticsFetch } = this.props;

    const requestId = ++this.requestId;

    (analyticsFetch || fetch)(
      getQuery({
        startTime: startDate.unix(),
        endTime: endDate.unix(),
        aggType: aggType || selectedAgg.value,
        ...(this.props.isMobile && { groupBy: [mobileAggKey] }),
      }),
      this.props.mode,
    )
      .then((resp) => {
        // dealyed response
        if (requestId !== this.requestId) {
          return null;
        }

        if (!resp.success) {
          return API_ERROR;
        }

        if (!resp.data || !resp.data.agg) {
          return API_INVALID_RESP;
        }

        const agg = resp.data.agg;

        if (!agg.result || agg.result.length === 0) {
          trackNoData(
            `${sectionTitle} from ${startDate.format(csvDateFormat)} to ${endDate.format(
              csvDateFormat,
            )}`,
          );
        }

        this.setState({
          data: agg.result,
          lastUpdatedAt: agg.last_updated_at,
        });
        if (aggType) {
          selfServeTrackSuccess({
            selfServeAction: 'Payment Insights Fetched',
            page: 'Home',
            screen: 'Home',
          });
        }
        return resp;
      })
      .catch((err) => {
        if (window.APP_ENV !== 'production') console.error(err);

        if (requestId !== this.requestId) {
          return null;
        }

        return API_ERROR;
      })
      .then((data) => {
        if (!data) {
          return;
        }

        // eslint-disable-next-line react/no-direct-mutation-state
        this.state.isLoading = false;

        if (data.error) {
          trackError(`Error while fetching data for Payment Methods`);

          // eslint-disable-next-line react/no-direct-mutation-state
          this.state.error = data.error;
          this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        // eslint-disable-next-line react/no-access-state-in-setstate
        this.setState({ ...this.state });
      });
  }

  onCSVData(csvUrl) {
    const { startDate, endDate } = this.props;
    const { selectedAgg } = this.state;

    this.setState({
      csvData: {
        name: `Payment Insights, ${moment(startDate).format(csvDateFormat)} to ${moment(
          endDate,
        ).format(csvDateFormat)} ${selectedAgg.text}(Razorpay).csv`,
        url: csvUrl,
      },
    });
  }

  onLevelChange(hierarchy) {
    this.setState({
      levels: getLevels(hierarchy),
      currentLevel: hierarchy,
      hierarchy,
    });
  }

  onAggChange({ option }) {
    selfServeTrackInitiate({
      selfServeAction: 'Payment Insights Fetched',
      page: 'Home',
      screen: 'Home',
    });
    const { startDate, endDate } = this.props;

    this.setState({
      selectedAgg: option,
    });

    this.fetchData(startDate, endDate, option.value);
  }

  componentDidMount() {
    const { startDate, endDate } = this.props;

    this.fetchData(startDate, endDate);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps;

    if (
      startDate.unix() !== this.props.startDate.unix() ||
      endDate.unix() !== this.props.endDate.unix()
    ) {
      this.fetchData(startDate, endDate);
    }
  }

  openReportModal(e) {
    e.preventDefault();

    this.props.openModal({
      component: null,
      size: 'large',
    });
  }

  // prettier-ignore
  trackDownload =
    ({ success }) =>
    () => {
      const selfServeTrack = success ? selfServeTrackSuccess : selfServeTrackInitiate;
      selfServeTrack({
        selfServeAction: 'Payment Insight Downloaded',
        page: 'Home',
        screen: 'Home',
      });
    };

  render() {
    const { data, error, levels, csvData, isLoading, selectedAgg } = this.state;
    const { startDate, endDate, sectionTitle, isMobile, user } = this.props;
    const levelsLength = levels.length;
    const hasNoData = !data || data.length === 0;

    const isJKOrg = isJKOfflineMerchant(this.props.org, user);

    if (isMobile) {
      const mobileComponentProps = {
        isLoading,
        hasNoData,
        selectedAgg,
        error,
        data,
        aggTypes,
        user,
      };

      mobileComponentProps.aggKey = mobileAggKey;
      mobileComponentProps.isCurrency = 'isCurrency' in selectedAgg;
      mobileComponentProps.onAggChange = this.onAggChange;

      return <Mobile {...mobileComponentProps} />;
    }

    return (
      <GenericPanel
        className="payment-methods-container"
        isLoading={isLoading}
        hasNoData={hasNoData}
        error={error}
      >
        <PanelTopbar className="clearfix">
          <div className="pull-left">
            <span>Showing:</span>
            {levelsLength > 0 && (
              <Breadcrumb>
                {levels.map((level, index) => (
                  <BreadcrumbItem
                    key={index}
                    onClick={() => {
                      trackBreadcrumbClick(level.data);

                      return index + 1 !== levelsLength && this.onLevelChange(level.data);
                    }}
                  >
                    {level.name}
                  </BreadcrumbItem>
                ))}
              </Breadcrumb>
            )}
          </div>
          <div className="panel-actions pull-right">
            <div className="panel-action-item">
              <GroupingDropdown
                grouping={aggTypes}
                onGroupChange={this.onAggChange}
                selectedGrouping={selectedAgg}
              />
            </div>
            <div className="panel-action-item">
              <MoreOptionsButton
                csvData={csvData}
                sectionTitle={sectionTitle}
                handleClick={this.trackDownload({ success: false })}
                handleCSVDownload={this.trackDownload({ success: true })}
                handleImageDownload={this.trackDownload({ success: true })}
              />
            </div>
          </div>
        </PanelTopbar>
        <PanelBody>
          <D3ScriptLoaderHoc
            Fallback={() => <PanelFallback title="Oh snap! Couldn’t load graph data." />}
          >
            <SuspenseWithLoader>
              <Treemap
                data={this.state.data}
                isCurrency={'isCurrency' in selectedAgg}
                onLevelChange={this.onLevelChange}
                currentLevel={this.state.currentLevel}
                onCSVData={this.onCSVData}
              />
            </SuspenseWithLoader>
          </D3ScriptLoaderHoc>
        </PanelBody>
        <PanelFooter className="clearfix">
          <div className="pull-left">
            <LastUpdated at={this.state.lastUpdatedAt} />
          </div>
          <div className="pull-right">
            <Link
              target={isJKOrg ? '_self' : '_blank'}
              rel="noreferrer noopener"
              to={`/payments?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`}
              onClick={() => trackGoToLinks('Payments', sectionTitle)}
            >
              View all payments from this date range
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default compose(
  connect((state) => ({ user: state.session.user, org: state.session.org }), {
    ...ModalActions,
    showNotification,
  }),
)(PaymentMethods);

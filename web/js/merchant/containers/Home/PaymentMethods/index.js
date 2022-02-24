import moment from 'moment';
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Breadcrumb, { BreadcrumbItem } from 'common/ui/Breadcrumb';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Treemap from 'merchant/containers/Home/PaymentMethods/Treemap';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/containers/Home/MoreOptionsButton';
import { fetch } from 'merchant/reducers/pokedex';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { API_ERROR, API_INVALID_RESP } from 'merchant/components/Home/data';
import { trackGoToLinks, trackNoData, trackError } from 'merchant/containers/Home/ga';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';

import Mobile from './Mobile';
import { trackBreadcrumbClick } from './ga';
import { getQuery, aggTypes } from './data';

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

@connect(null, { ...ModalActions, showNotification })
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

    this.onLevelChange = ::this.onLevelChange;
    this.onCSVData = ::this.onCSVData;
    this.openReportModal = ::this.openReportModal;
    this.onAggChange = ::this.onAggChange;
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

        return resp;
      })
      .catch((err) => {
        console.error(err);

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
    const { startDate, endDate } = this.props;

    this.setState({
      selectedAgg: option,
    });

    this.fetchData(startDate, endDate, option.value);
  }

  UNSAFE_componentWillMount() {
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

  render() {
    const { data, error, levels, csvData, isLoading, selectedAgg } = this.state;
    const { startDate, endDate, sectionTitle, isMobile } = this.props;
    const levelsLength = levels.length;
    const hasNoData = !data || data.length === 0;

    if (isMobile) {
      const mobileComponentProps = {
        isLoading,
        hasNoData,
        selectedAgg,
        error,
        data,
        aggTypes,
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
              <MoreOptionsButton csvData={csvData} sectionTitle={sectionTitle} />
            </div>
          </div>
        </PanelTopbar>
        <PanelBody>
          <Treemap
            data={this.state.data}
            isCurrency={'isCurrency' in selectedAgg}
            onLevelChange={this.onLevelChange}
            currentLevel={this.state.currentLevel}
            onCSVData={this.onCSVData}
          />
        </PanelBody>
        <PanelFooter className="clearfix">
          <div className="pull-left">
            <LastUpdated at={this.state.lastUpdatedAt} />
          </div>
          <div className="pull-right">
            <Link
              target="_blank"
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

export default PaymentMethods;

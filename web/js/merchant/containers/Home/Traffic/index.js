import React, { Component } from 'react';
import { Doughnut } from 'react-chartjs-2';
import { connect } from 'react-redux';
import debounce from 'common/utils/debounce';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetch } from 'merchant/reducers/pokedex';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { groupValues, groupMeta, getQuery, getPieData } from './data';
import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/containers/Home/MoreOptionsButton';
import { API_ERROR, API_INVALID_RESP, getPlatformColor } from 'merchant/components/Home/data';
import { trackError, trackNoData } from 'merchant/containers/Home/ga';

import Mobile from 'merchant/containers/Home/Traffic/Mobile';

const aggTypes = groupValues.map((value) => groupMeta[value]);

const chartOptions = {
  tooltips: {
    enabled: false,
  },
  layout: {
    padding: {
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
    },
  },
};
const csvDateFormat = 'DD-MM-YYYY';

class Traffic extends Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      selectedGrouping: groupMeta[groupValues[0]],
      groupsState: {},
      windowWidth: window.innerWidth,
    };

    groupValues.forEach((groupValue) => {
      // eslint-disable-next-line react/no-direct-mutation-state
      this.state.groupsState[groupValue] = {
        loading: false,
        chartData: null,
        legendData: null,
        error: '',
      };
    });

    this.requestId = 0;

    this.onGroupChange = ::this.onGroupChange;
    this.handleImageExportClick = ::this.handleImageExportClick;
    this.handleResize = debounce(::this.handleResize, 250);

    this.data = null;
  }

  getData(isInitialLoad, startDate, endDate) {
    startDate = startDate || this.props.startDate;
    endDate = endDate || this.props.endDate;

    const { sectionTitle, analyticsFetch } = this.props;

    const { selectedGrouping, groupsState } = this.state;
    const groupState = groupsState[selectedGrouping.value];
    const meta = groupMeta[selectedGrouping.value];
    const query = getQuery({
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      group: selectedGrouping.value,
    });

    if (isInitialLoad) {
      this.setState({
        loading: false,
      });
    }

    groupState.loading = true;
    groupState.error = '';

    // I have no clue what this is 😟
    // eslint-disable-next-line react/no-access-state-in-setstate
    this.setState(this.state);

    const downloadFileName = `Platform traffic split, ${startDate.format(
      csvDateFormat,
    )} to ${endDate.format(csvDateFormat)}, ${meta.title}(Razorpay)`;

    const requestId = ++this.requestId;

    (analyticsFetch || fetch)(query, this.props.mode)
      .then((resp) => {
        if (requestId !== this.requestId) {
          return null;
        }

        if (!resp.success) {
          return API_ERROR;
        }

        if (!resp.data || !resp.data.distribution) {
          return API_INVALID_RESP;
        }

        const distribution = resp.data.distribution;

        const { labels, datasets, legendData, csv } = getPieData({
          data: distribution.result,
          groupByColumnName: meta.groupBy,
          isCurrency: meta.isCurrency,
          groupTitleMap: { Mobile: 'mWeb' },
          getColor: getPlatformColor,
        });

        if (labels.length === 0) {
          trackNoData(
            `${sectionTitle} from ${startDate.format(csvDateFormat)} to ${endDate.format(
              csvDateFormat,
            )}`,
          );
        }

        groupState.chartData = { labels, datasets };
        groupState.legendData = legendData;
        groupState.lastUpdatedAt = distribution.last_updated_at;
        groupState.csvData = {
          name: `${downloadFileName}.csv`,
          url: csv,
        };
        groupState.pngData = {
          name: `${downloadFileName}.png`,
          url: '',
        };

        return resp;
      })
      .catch((err) => {
        if (requestId !== this.requestId) {
          return null;
        }

        console.error(err);

        return API_ERROR;
      })
      .then((data) => {
        if (!data) {
          return null;
        }

        if (isInitialLoad) {
          this.setState({
            loading: false,
          });
        }

        groupState.loading = false;

        if (data.error) {
          trackError(`Error while fetching data for traffic section - ${selectedGrouping.value}`);

          this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });

          groupState.error = data.error;
        }

        // I have no clue what this is 😟
        // eslint-disable-next-line react/no-access-state-in-setstate
        this.setState(this.state);

        return '';
      });
  }

  componentWillMount() {
    this.getData();
  }

  onGroupChange({ option: selectedGrouping }) {
    this.setState(
      {
        selectedGrouping,
      },
      () => {
        return this.getData();
      },
    );
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps;
    const props = this.props;

    if (
      startDate.toDate() !== props.startDate.toDate() ||
      endDate.toDate() !== props.endDate.toDate()
    ) {
      this.getData(false, startDate, endDate);
    }
  }

  handleImageExportClick(e) {
    analyticsTrack({
      objectName: 'download chart',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        downloadType: 'image',
        graphType: '',
        success: true,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const { selectedGrouping, groupsState } = this.state;
    const groupState = groupsState[selectedGrouping.value];
    const anchor = e.target;

    if (!groupState.pngData.url) {
      e.preventDefault();

      import('common/utils/screenshot').then((module) => {
        const takeScreenshot = module.default;
        takeScreenshot(this.panelBody).then((url) => {
          groupState.pngData.url = url;

          // eslint-disable-next-line react/no-access-state-in-setstate
          this.setState(this.state, () => {
            anchor.click();
          });
        });
      });
    }
  }

  setChartSize() {
    if (!this.chartContent) {
      return;
    }

    const { width } = this.chartContent.getBoundingClientRect();

    // fixing with and height of chart container so that
    // the chart size would not grow
    this.chartContent.style.width = `${width}px`;
  }

  handleResize() {
    return this.setState(
      {
        hideChart: true,
      },
      () => {
        if (!this.chartContent) {
          return;
        }

        this.chartContent.style.width = '100%';

        window.setTimeout(() => {
          this.setChartSize();
          this.setState({ hideChart: false });
        });
      },
    );
  }

  componentDidMount() {
    if (this.props.isMobile) {
      return;
    }

    this.setChartSize();

    window.addEventListener('resize', this.handleResize);
  }

  componentWillUnmount() {
    if (this.props.isMobile) {
      return;
    }

    window.removeEventListener('resize', this.handleResize);
  }

  render() {
    const { loading, selectedGrouping, groupsState } = this.state;
    const groupState = groupsState[selectedGrouping.value];
    const { isCurrency } = groupMeta[selectedGrouping.value];
    const { chartData, legendData } = groupState;
    const hasNoData = !chartData || chartData.labels.length === 0;
    const { sectionTitle, isMobile } = this.props;

    if (isMobile) {
      const mobileProps = {
        isLoading: loading || groupState.loading,
        hasNoData,
        error: groupState.error,
        data: legendData,
        aggTypes,
        onAggChange: this.onGroupChange,
        selectedAgg: selectedGrouping,
        isCurrency,
      };
      return <Mobile {...mobileProps} />;
    }

    return (
      <GenericPanel
        id="traffic-split"
        className="rzp-traffic p-all"
        isLoading={loading || groupState.loading}
        hasNoData={hasNoData}
        error={groupState.error}
      >
        <PanelTopbar className="clearfix">
          <div className="panel-actions pull-right">
            <div className="panel-action-item">
              <GroupingDropdown
                grouping={aggTypes}
                selectedGrouping={selectedGrouping}
                onGroupChange={this.onGroupChange}
                displayTextKey="title"
                sectionTitle={sectionTitle}
              />
            </div>
            <div className="panel-action-item">
              <MoreOptionsButton
                handleImageDownload={this.handleImageExportClick}
                csvData={groupState.csvData}
                pngData={groupState.pngData}
                sectionTitle={sectionTitle}
              />
            </div>
          </div>
        </PanelTopbar>
        <PanelBody>
          <div className="chart-row" ref={(node) => (this.panelBody = node)}>
            <div className="column">
              <div className="chart-content" ref={(node) => (this.chartContent = node)}>
                {!groupState.loading && chartData && !this.state.hideChart && (
                  <Doughnut
                    ref={(node) => (this.chartInstance = node)}
                    options={chartOptions}
                    data={chartData}
                    windowWidth={this.state.windowWidth}
                  />
                )}
              </div>
            </div>
            <div className="column">
              {!groupState.loading && legendData && (
                <Legend
                  data={groupState.legendData}
                  alignment="vertical"
                  isCurrency={isCurrency}
                  tooltipAlign="right"
                />
              )}
            </div>
          </div>
        </PanelBody>
        <PanelFooter>
          <div className="pull-left">
            <LastUpdated at={groupState.lastUpdatedAt} />
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default connect(null, { showNotification })(Traffic);

import React, { Component } from 'react';
import { Pie } from 'react-chartjs-2';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import takeScreenshot from 'rzp/utils/screenshot';
import { showNotification } from 'rzp/modules/notifications';

import { fetch } from 'merchant/modules/pokedex';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { groupValues, groupMeta, getQuery, getPieData } from './data';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';
import { API_ERROR, API_INVALID_RESP } from 'merchant/components/Home/data';

import './styles.styl';

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
  },
  csvDateFormat = 'DD-MM-YYYY';

@connect(null, { showNotification })
class Traffic extends Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      selectedGrouping: groupMeta[groupValues[0]],
      groupsState: {},
    };

    groupValues.forEach(groupValue => {
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

    this.data = null;
  }

  getData(isInitialLoad, startDate, endDate) {
    startDate = startDate || this.props.startDate;
    endDate = endDate || this.props.endDate;

    const { selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping.value],
      meta = groupMeta[selectedGrouping.value],
      query = getQuery({
        startTime: startDate.unix(),
        endTime: endDate.unix(),
        group: selectedGrouping.value,
      });

    if (isInitialLoad) {
      this.state.loading = true;
    }

    groupState.loading = true;
    groupState.error = '';

    this.setState(this.state);

    const downloadFileName = `Platform traffic split, ${startDate.format(
      csvDateFormat
    )} to ${endDate.format(csvDateFormat)}, ${meta.title}(Razorpay)`;

    const requestId = ++this.requestId;

    fetch(query, this.props.mode)
      .then(resp => {
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
        });

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
      .catch(err => {
        if (requestId !== this.requestId) {
          return null;
        }

        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        if (!data) {
          return null;
        }

        if (isInitialLoad) {
          this.state.loading = false;
        }

        groupState.loading = false;

        if (data.error) {
          this.props.showNotification({
            type: 'error',
            message: data.error,
          });

          groupState.error = data.error;
        }

        this.setState(this.state);
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
      }
    );
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps,
      props = this.props;

    if (
      startDate.toDate() !== props.startDate.toDate() ||
      endDate.toDate() !== props.endDate.toDate()
    ) {
      this.getData(false, startDate, endDate);
    }
  }

  handleImageExportClick(e) {
    const { selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping.value],
      anchor = e.target;

    if (!groupState.pngData.url) {
      e.preventDefault();

      takeScreenshot(this.panelBody).then(url => {
        groupState.pngData.url = url;

        this.setState(this.state, () => {
          anchor.click();
        });
      });
    }
  }

  componentDidMount() {
    const { width, height } = this.chartContent.getBoundingClientRect();

    // fixing with and height of chart container so that
    // the chart size would not grow
    this.chartContent.style.width = width + 'px';
    this.chartContent.style.height = height + 'px';
  }

  render() {
    const { loading, selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping.value],
      { isCurrency } = groupMeta[selectedGrouping.value],
      { chartData, legendData } = groupState,
      hasNoData = !chartData || chartData.labels.length === 0,
      { startDate, endDate } = this.props;

    return (
      <GenericPanel
        className="rzp-traffic p-all"
        isLoading={loading || groupState.loading}
        hasNoData={hasNoData}
        error={groupState.error}
      >
        <PanelTopbar className="clearfix">
          <div className="panel-actions pull-right">
            <div className="panel-action-item">
              <GroupingDropdown
                grouping={groupValues.map(value => groupMeta[value])}
                selectedGrouping={selectedGrouping}
                onGroupChange={this.onGroupChange}
                displayTextKey="title"
              />
            </div>
            <div className="panel-action-item">
              <MoreOptionsButton
                onImageExport={this.handleImageExportClick}
                csvData={groupState.csvData}
                pngData={groupState.pngData}
              />
            </div>
          </div>
        </PanelTopbar>
        <PanelBody>
          <div className="chart-row" ref={node => (this.panelBody = node)}>
            <div className="column">
              <div
                className="chart-content"
                ref={node => (this.chartContent = node)}
              >
                {!groupState.loading &&
                  chartData && <Pie options={chartOptions} data={chartData} />}
              </div>
            </div>
            <div className="column">
              {!groupState.loading &&
                legendData && (
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
          <div className="pull-right">
            <Link
              target="_blank"
              to={`/payments?from=${startDate.unix()}&to=${endDate.unix()}`}
            >
              View all Payments
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default Traffic;

import React, { Component } from 'react';
import { Pie } from 'react-chartjs-2';
import { connect } from 'react-redux';

import { getPieData } from 'rzp/utils/chart/transformers';
import { paiseToRupees, shortenText, titleCase } from 'rzp/utils/rzp-utils';
import takeScreenshot from 'rzp/utils/screenshot';

import { fetch } from 'merchant/modules/pokedex';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { groupValues, groupMeta, getQuery } from './data';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';

import './styles.styl';

const chartOptions = {
    tooltips: {
      enabled: false,
    },
  },
  csvDateFormat = 'DD-MM-YYYY';

@connect(null, null)
class Traffic extends Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      selectedGrouping: groupValues[0],
      groupsState: {},
    };

    groupValues.forEach(groupValue => {
      this.state.groupsState[groupValue] = {
        loading: false,
        chartData: null,
        legendData: null,
      };
    });

    this.onGroupChange = ::this.onGroupChange;
    this.handleImageExportClick = ::this.handleImageExportClick;

    this.data = null;
  }

  getData(isInitialLoad, startDate, endDate) {
    startDate = startDate || this.props.startDate;
    endDate = endDate || this.props.endDate;

    const { selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping],
      meta = groupMeta[selectedGrouping],
      query = getQuery({
        merchantId: '10000000000000',
        startTime: startDate.unix(),
        endTime: endDate.unix(),
        group: selectedGrouping,
      });

    if (isInitialLoad) {
      this.state.loading = true;
    }

    groupState.loading = true;

    this.setState(this.state);

    const downloadFileName = titleCase(
      shortenText(
        `Platform traffic split \u05C0 ${startDate.format(
          csvDateFormat
        )} - ${endDate.format(csvDateFormat)} \u05C0 ${meta.title}`
      )
    );

    fetch(query).then(({ data: { distribution } }) => {
      const { labels, datasets, legendData, csv } = getPieData({
        data: distribution.result,
        groupByColumnName: meta.groupBy,
        valueTransformer: meta.isCurrency && paiseToRupees,
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

      if (isInitialLoad) {
        this.state.loading = false;
      }

      groupState.loading = false;

      this.setState(this.state);
    });
  }

  componentWillMount() {
    this.getData();
  }

  onGroupChange(e) {
    const selectedGrouping = e.target.value,
      groupState = this.state.groupsState[selectedGrouping];

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
      groupState = groupsState[selectedGrouping],
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

  render() {
    const { loading, selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping],
      { isCurrency } = groupMeta[selectedGrouping],
      { chartData, legendData } = groupState,
      hasNoData = !chartData || chartData.labels.length === 0;

    return (
      <GenericPanel
        className="rzp-traffic p-all"
        isLoading={loading || groupState.loading}
        hasNoData={hasNoData}
      >
        <PanelTopbar className="clearfix">
          <div className="panel-actions pull-right">
            <div className="panel-action-item">
              <select
                value={selectedGrouping}
                onChange={this.onGroupChange}
                className="form-control"
              >
                {groupValues.map((value, index) => {
                  return (
                    <option value={value} key={index}>
                      {groupMeta[value].title}
                    </option>
                  );
                })}
              </select>
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
          <div className="row" ref={node => (this.panelBody = node)}>
            <div className="col-md-5 col-sm-12 column">
              {!groupState.loading &&
                chartData && <Pie options={chartOptions} data={chartData} />}
            </div>
            <div className="col-md-7 col-sm-12 column">
              {!groupState.loading &&
                legendData && (
                  <Legend
                    data={groupState.legendData}
                    alignment="vertical"
                    valueTransformer={
                      isCurrency
                        ? humanReadableIndianCurrency
                        : humanReadableIndian
                    }
                  />
                )}
            </div>
          </div>
        </PanelBody>
        <PanelFooter>
          <LastUpdated at={groupState.lastUpdatedAt} />
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default Traffic;

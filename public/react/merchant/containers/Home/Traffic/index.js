import React, { Component } from 'react';
import { Pie } from 'react-chartjs-2';
import { connect } from 'react-redux';

import { getPieData } from 'rzp/utils/chart/transformers';
import { paiseToRupees, shortenText, titleCase } from 'rzp/utils/rzp-utils';

import { fetch } from 'merchant/modules/pokedex';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
  shorte,
} from 'rzp/utils/numerals';
import { groupValues, groupMeta, getQuery } from './data';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';

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

  render() {
    const { loading, selectedGrouping, groupsState } = this.state,
      groupState = groupsState[selectedGrouping],
      { isCurrency } = groupMeta[selectedGrouping],
      { chartData, legendData } = groupState;

    return (
      <div className="panel rzp-traffic p-all">
        <div className="clearfix panel-actions">
          <div className="pull-right">
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
              <MoreOptionsButton csvData={groupState.csvData} />
            </div>
          </div>
        </div>
        <div className="row">
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
        <div className="panel-footer">
          <LastUpdated at={groupState.lastUpdatedAt} />
        </div>
      </div>
    );
  }
}

export default Traffic;

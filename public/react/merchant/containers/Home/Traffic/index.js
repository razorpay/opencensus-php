import React, { Component } from 'react';
import { Pie } from 'react-chartjs-2';
import { connect } from 'react-redux';

import { getPieData } from 'rzp/utils/chart/transformers';

import { fetch } from 'merchant/modules/pokedex';
import { groupValues, groupMeta, getQuery } from './data';
import Legend from 'merchant/components/Home/Legend';

const chartOptions = {
  tooltips: {
    enabled: false,
  },
};

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

    fetch(query).then(resp => {
      const { labels, datasets, legendData } = getPieData({
        data: resp.data.distribution,
        groupByColumnName: meta.groupBy,
      });

      groupState.chartData = { labels, datasets };
      groupState.legendData = legendData;

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
      { chartData, legendData } = groupState;

    return (
      <div className="panel rzp-traffic p-all">
        <div className="clearfix">
          <div className="panel-actions p-b pull-right">
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
              <button className="btn btn-default">...</button>
            </div>
          </div>
        </div>
        <div className="row">
          <div className="col-md-6 col-sm-12">
            {!groupState.loading &&
              chartData && <Pie options={chartOptions} data={chartData} />}
          </div>
          <div className="col-md-5 col-sm-12">
            {!groupState.loading &&
              legendData && (
                <Legend data={groupState.legendData} alignment="vertical" />
              )}
          </div>
        </div>
      </div>
    );
  }
}

export default Traffic;

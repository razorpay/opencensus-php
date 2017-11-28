import React, { Component } from 'react';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'merchant/containers/Home/Legend';
import { getTraffic } from 'merchant/models/HomeKeyMetricsMock';

import { colors } from 'rzp/utils/chart/index.js';
import { Pie } from 'react-chartjs-2';

import './styles.styl';

const chartOptions = {
  data: {
    datasets: [
      {
        backgroundColor: colors,
        hoverBackgroundColor: colors,
        borderWidth: 0,
      },
    ],
  },
  options: {
    tooltips: {
      enabled: false,
    },
  },
};

const groupValues = ['transactionVolume', 'noTransactions'];

const groups = [
  {
    name: 'By Teansaction Volume',
    value: groupValues[0],
  },
  {
    name: 'By No. of Transactions',
    value: groupValues[1],
  },
];

class Traffic extends Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      chartData: [],
      legendData: [],
      selectedGrouping: groups[0].value,
    };

    this.onGroupChange = this.onGroupChange.bind(this);

    this.data = null;
  }

  componentWillMount() {
    this.getData();
  }

  getData() {
    const { selectedGrouping } = this.state;

    this.setState({ loading: true });

    getTraffic().then(resp => {
      this.data = {};

      groupValues.forEach(groupName => {
        const sumData = resp[`${groupName}Count`],
          percentageData = resp[`${groupName}Percentage`];
        const groupData = (this.data[groupName] = {});

        /*
         * platform order needs to be maintained , coz
         * the order is not guaranteed between the two
         * aggregations
         */
        const sumDataMap = {},
          platformOrder = [];

        groupData.chartData = sumData.map(item => {
          sumDataMap[item.platform] = item.value;
          platformOrder.push(item.platform);
          return item.value;
        });

        groupData.legendData = [];

        percentageData.forEach((item, index) => {
          const platformIndex = platformOrder.indexOf(item.platform);

          groupData.legendData[platformIndex] = {
            percentage: item.value,
            title: item.platform,
            content: sumDataMap[item.platform],
          };
        });
      });

      const { chartData, legendData } = this.data[selectedGrouping];

      this.setState({
        loading: false,
        chartData,
        legendData,
      });
    });
  }

  onGroupChange(e) {
    const selectedGrouping = e.target.value,
      { chartData, legendData } = this.data[selectedGrouping];

    this.setState({
      selectedGrouping,
      chartData,
      legendData,
    });
  }

  render() {
    const { loading, chartData, legendData, selectedGrouping } = this.state;

    chartOptions.data.datasets[0].data = chartData;

    return (
      <div className="panel rzp-traffic p-all">
        <div className="clearfix">
          <div className="pull-right">...</div>
          <div className="pull-right">
            <select value={selectedGrouping} onChange={this.onGroupChange}>
              {groups.map((item, index) => {
                return (
                  <option value={item.value} key={index}>
                    {item.name}
                  </option>
                );
              })}
            </select>
          </div>
        </div>
        <div className="chart-container">
          {loading ? (
            <span>Loading...</span>
          ) : (
            <div className="chart-content">
              <div className="chart">
                <Pie {...chartOptions} />
              </div>
              <Legend alignment="vertical">
                {legendData.map((item, index) => {
                  return (
                    <LegendItem key={index}>
                      <LegendLabel color={colors[index]}>
                        {item.percentage}%
                      </LegendLabel>
                      <LegendTitle>{item.title}</LegendTitle>
                      <LegendContent>{item.content}</LegendContent>
                    </LegendItem>
                  );
                })}
              </Legend>
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default Traffic;

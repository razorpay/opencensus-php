import React, { Component } from 'react';

import Definition from 'rzp/ui/Definition';
import Highcharts from 'rzp/ui/Highcharts';
import defaultChartOptions from 'rzp/ui/Highcharts/defaults';
import Change from 'rzp/ui/Change';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'merchant/containers/Home/Legend';

import { tabsMeta } from './data';

const chartOptions = {
  chart: {
    height: 250,
    spacingTop: 10,
  },
  xAxis: {
    type: 'datetime',
  },
  plotOptions: {
    area: {
      stacking: true,
      animation: {
        duration: 0,
      },
    },
  },
};

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];
    this.handleGroupingChange = this.handleGroupingChange.bind(this);
  }

  handleGroupingChange(e) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, e.target.value);
  }

  render() {
    const { selectedGrouping, data, startDate, endDate } = this.props,
      dateFormat = 'DD MMM YYYY',
      histogramOptions = {
        ...chartOptions,
        series:
          data.histogram &&
          data.histogram.map(item => {
            return {
              type: 'area',
              data: item.data,
              name: item.name,
            };
          }),
      },
      { grouping, options } = this.meta,
      legendData =
        data.histogram &&
        data.histogram.map((data, index) => {
          return {
            title: data.name,
            percentage: data.percentage.value,
            content: data.sum.value,
            color: defaultChartOptions.colors[index],
          };
        });

    if (!data.diff) {
      return <center>Loading...</center>;
    }

    return (
      <div className="panel">
        <div className="clearfix p-all">
          <div className="pull-left">
            <Definition>
              <h4>
                <Change value={data.diff.value} />
              </h4>
              <span className="text-fade">
                As compared to: {startDate.format(dateFormat)} to{' '}
                {endDate.format(dateFormat)}
              </span>
            </Definition>
          </div>
          <div className="pull-right">
            {grouping.length > 0 && (
              <select
                value={selectedGrouping}
                onChange={this.handleGroupingChange}
              >
                {grouping.map((item, index) => {
                  return (
                    <option value={item.value} key={index}>
                      {item.text}
                    </option>
                  );
                })}
              </select>
            )}
            {options.length > 0 && <button>...</button>}
          </div>
        </div>
        <div className="chart-container">
          <Highcharts options={histogramOptions} />
          <Legend>
            {legendData.map((data, index) => {
              return (
                <LegendItem key={index}>
                  <LegendLabel color={data.color}>
                    {data.percentage}%
                  </LegendLabel>
                  <LegendTitle>{data.title}</LegendTitle>
                  <LegendContent>{data.content}</LegendContent>
                </LegendItem>
              );
            })}
          </Legend>
        </div>
      </div>
    );
  }
}

export default Panel;

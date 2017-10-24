import React, { Component } from 'react';
import { observable } from 'mobx';
import { notifyError } from 'common/modal';
import { adminPost } from 'util/fetch';
import { Single, Chart, getPriceChartOptions } from './graphs';
import { getFormattedAmount } from 'util/index';

const defaultData = {
  merchant_id: '10000000000000',
  type: 'sum',
  interval: '',
  group: '',
  filter: '',
};

class Stat {
  constructor(data) {
    this.data = observable(data || defaultData);
    this.component = observable.shallowBox(<div class="spinner" />);
  }

  getTitle() {
    var data = this.data;
    if (data.type === 'summary') {
      return 'Summary';
    }

    var title = data.type === 'sum' ? 'Payment Volume' : 'Success Rate';
    if (data.filter) {
      title = data.filter + ' ' + title;
    }
    if (data.interval) {
      title = data.interval + ' ' + title;
    }
    if (data.group) {
      title = ' By ' + data.group;
    }
    return title;
  }

  fetch() {
    var data = this.data;
    var filters = {
      merchant_id: [data.merchant_id],
      created_at: {
        gte: (data.from && parseInt(new Date(data.from).getTime() / 1000)) || 0,
        lte:
          data.to &&
          parseInt((new Date(data.to).getTime() || Date.now()) / 1000),
      },
    };

    var details = {
      index: 'payments',
    };

    var group_by = [];

    if (data.group) {
      group_by.push(data.group);
    }

    if (data.interval) {
      group_by.push(data.interval);
    }

    if (group_by.length) {
      details.group_by = group_by;
    }

    if (data.type.startsWith('sum')) {
      details.column = 'base_amount';
    }

    if (data.method) {
      filters.method = [data.method];
    }

    var body = {
      filters: {
        default: [filters],
      },
      aggregations: {
        result: {
          agg_type: data.type,
          details,
        },
      },
    };

    return adminPost({
      merchant_id: data.merchant_id,
      body,
      route_name: 'merchant_analytics',
    }).then(response => {
      var result;
      if (response) {
        result = response.result;
      } else {
        return;
      }

      var title = this.getTitle();
      let legends = [];

      if (result.length === 1) {
        let displayValue = result[0].value;
        if (details.column === 'base_amount') {
          displayValue = '₹' + displayValue;
        } else if (details.agg_type === 'success_rate') {
          displayValue = '%' + displayValue;
        }
        this.component.set(<Single title={title} value={displayValue} />);
      } else if (result.length > 0) {
        if (result[0].timestamp) {
          // This is a time-oriented result
          let timeData = {
            labels: [],
            series: [],
          };

          // If we have more than two keys in a result, it has to be time-series
          if (Object.keys(result[0]).length > 2) {
            /**
             * First, let's create an object like
             * {
             *    timestamp1: {
             *      method1: value
             *    },
             *    timestamp2: {
             *      method1: value,
             *      method2: value
             *    }
             * }
             */
            let series = {};
            result.forEach(function(element) {
              const { timestamp, method, value } = element;
              if (!series[timestamp]) {
                series[timestamp] = {};
              }
              series[timestamp][method] = value;
            });

            // The keys of the object created above are the labels
            timeData.labels = Object.keys(series);

            /**
             * Now, let's initialize an object like
             * {
             *    method1: [],
             *    method2: []
             * }
             */
            let multiLineSeries = {};
            result.forEach(function(element) {
              if (!multiLineSeries[element.method]) {
                multiLineSeries[element.method] = [];
              }
            });

            /**
             * Now, we will populate the array in each value of `multiLineSeries`.
             * This is important because the methods whose data does not exist in a given label
             * need to have `null` as the value at that index.
             */
            const methods = Object.keys(multiLineSeries);
            for (let timestamp in series) {
              for (let m_index in methods) {
                multiLineSeries[methods[m_index]].push(
                  series[timestamp][methods[m_index]] || null
                );
              }
            }

            // The values for the series are the arrays created in `multiLineSeries`
            timeData.series = Object.values(multiLineSeries);

            legends = Object.keys(multiLineSeries);
            console.log('legends', legends);
          } else {
            timeData.series.push([]);
            result.forEach(function(element) {
              timeData.labels.push(element.timestamp);
              timeData.series[0].push(element.value);
            }, this);
          }

          this.component.set(
            <Chart
              type="line"
              title={title}
              data={timeData}
              options={getPriceChartOptions('line', {
                ...details,
                maxLabels: 15,
              })}
              legends={legends}
            />
          );
        } else {
          // This is not a time-oriented result
          let barData = {
            labels: [],
            series: [[]],
          };
          result.forEach(function(element) {
            barData.labels.push(element.method);
            barData.series[0].push(element.value);
          }, this);

          this.component.set(
            <Chart
              type="bar"
              title={title}
              data={barData}
              options={getPriceChartOptions('bar', details)}
            />
          );
        }
      }
    });
  }
}

export default class StatsModel {
  constructor() {
    this.selected = observable.shallowBox(0);
    this.items = observable.shallowArray([new Stat()]);
  }

  setSelected = e => {
    this.selected.set(Number(e.currentTarget.getAttribute('data-key') || 0));
    e.stopPropagation();
  };

  getData() {
    return this.items[this.selected.get()].data;
  }

  submit(data) {
    var selected = this.selected.get();
    var s;
    if (selected) {
      s = this.items[selected];
    } else {
      s = new Stat(Object.assign({}, this.items[0].data));
      this.selected.set(this.items.push(s) - 1);
    }
    return s.fetch();
  }
}

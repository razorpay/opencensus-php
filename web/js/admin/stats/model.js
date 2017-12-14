import React, { Component } from 'react';
import { observable } from 'mobx';
import { notifyError } from 'common/modal';
import { adminPost } from 'util/fetch';
import {
  Single,
  ChartView,
  getPriceChartOptions,
  tooltipYLabelPrefix,
  tooltipYLabelSuffix,
} from './graphs';
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
        gte: data.from,
        lte: data.to,
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
        result = response.recent_payments;
      } else {
        return;
      }

      var title = this.getTitle();

      // If there's just one result, display it directly
      if (result.length === 1) {
        let displayValue = result[0].value;
        if (details.column === 'base_amount') {
          displayValue = '₹' + displayValue;
        } else if (details.agg_type === 'success_rate') {
          displayValue = '%' + displayValue;
        }
        this.component.set(<Single title={title} value={displayValue} />);
      } else if (result.length > 0) {
        // There are multiple results now, we need to show a chart

        // Sort the results by timestamp before operating on data
        result.sort((a, b) => {
          if (a.timestamp > b.timestamp) {
            return 1;
          } else if (a.timestamp < b.timestamp) {
            return -1;
          } else {
            return 0;
          }
        });

        if (result[0].timestamp) {
          // This is a time-oriented result
          let timeData = {
            labels: [], // Elements for X-Axis
            datasets: [], // Elements for Y-Axis
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
             *    },
             *    ...
             * }
             */
            let series = {};
            result.forEach(function(element) {
              const { timestamp, method, value } = element;
              // Add the `timestamp` key to `series` if it does not exist
              if (!series[timestamp]) {
                series[timestamp] = {};
              }

              // Set the value of `method` corresponding to the `timestamp`
              series[timestamp][method] = value;
            });

            // The keys of the object created above are the labels
            timeData.labels = Object.keys(series);

            /**
             * Now, let's initialize an object like
             * {
             *    method1: [],
             *    method2: [],
             *    ...
             * }
             */
            let multiLineSeries = {};
            result.forEach(function(element) {
              if (!multiLineSeries[element.method]) {
                multiLineSeries[element.method] = [];
              }
            });

            /**
             * Now, we will populate the array in each key of `multiLineSeries`.
             * This is required because the methods whose data does not exist in a given label
             * need to have `null` as the value at that index.
             */
            const methods = Object.keys(multiLineSeries); // Get all the methods that exist in the result
            for (let timestamp in series) {
              for (let m_index in methods) {
                multiLineSeries[methods[m_index]].push(
                  series[timestamp][methods[m_index]] || null
                );
              }
            }

            // Add each method as a separate dataset
            for (let methodName in multiLineSeries) {
              timeData.datasets.push({
                label: methodName,
                data: multiLineSeries[methodName],
              });
            }
          } else {
            // Add a dataset
            timeData.datasets.push({
              label: 'Amount', // Label for this dataset
              data: [], // Elements for Y-Axis
            });

            // Populate the dataset
            result.forEach(function(element) {
              timeData.labels.push(element.timestamp);
              timeData.datasets[0].data.push(element.value);
            });
          }

          // Add Rupee or Percentage symbol to the label
          let chartOptions = {};
          if (details.column === 'base_amount') {
            chartOptions = {
              ...chartOptions,
              tooltips: {
                callbacks: {
                  label: tooltipYLabelPrefix('₹'),
                },
              },
            };
          } else if (details.agg_type === 'success_rate') {
            chartOptions = {
              ...chartOptions,
              tooltips: {
                callbacks: {
                  label: tooltipYLabelSuffix('%'),
                },
              },
            };
          }

          // Convert timestamps to human-readable format
          // Because not everyone can parse epochs in their heads
          timeData.labels = timeData.labels.map((label, i) => {
            return new Date(label * 1000).toDateString();
          });

          // Generate and a chart using ChartView
          this.component.set(
            <ChartView
              type="line"
              title={title}
              data={timeData}
              options={getPriceChartOptions('line', chartOptions || {})}
            />
          );
        } else {
          // This is not a time-oriented result

          let barData = {
            labels: [], // Elements for X-Axis
            datasets: [
              {
                // List of datasets
                label: 'Amount', // Label for this dataset
                data: [], // Elements for Y-Axis
              },
            ],
          };

          // Populate the dataset
          result.forEach(function(element) {
            barData.labels.push(element.method);
            barData.datasets[0].data.push(element.value);
          }, this);

          // Add Rupee or Percentage symbol to the label
          let chartOptions = {};
          if (details.column === 'base_amount') {
            chartOptions = {
              ...chartOptions,
              tooltips: {
                callbacks: {
                  label: tooltipYLabelPrefix('₹'),
                },
              },
            };
          } else if (details.agg_type === 'success_rate') {
            chartOptions = {
              ...chartOptions,
              tooltips: {
                callbacks: {
                  label: tooltipYLabelSuffix('%'),
                },
              },
            };
          }

          // Generate and a chart using ChartView
          this.component.set(
            <ChartView
              type="bar"
              title={title}
              data={barData}
              options={getPriceChartOptions('bar', chartOptions || {})}
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

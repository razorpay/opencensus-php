import React, { Component } from 'react';
import { observable } from 'mobx';
import { notifyError } from 'common/modal';
import { adminPost } from 'util/fetch';
import { Single, Chart } from './graphs';
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

      if (result.length === 1) {
        this.component.set(
          <Single title={title} value={result[0].displayValue} />
        );
      } else if (result.length > 0) {
        if (result[0].timestamp) {
          // This is a time-oritented result
          let timeData = {
            labels: [],
            series: [[]],
          };
          result.forEach(function(element) {
            timeData.labels.push(element.timestamp);
            timeData.series[0].push(element.value);
          }, this);

          // We want to display at-most 10 labels, so we select a number we will perform MOD with
          let labelInterpolationMod = Math.ceil(timeData.labels.length / 10);
          let chartOptions = {
            showPoint: false,
            axisX: {
              showGrid: false,
              labelInterpolationFnc: function(value, index, labels) {
                if (index % labelInterpolationMod === 0)
                  return new Date(value * 1000).toDateString();
                return null;
              },
            },
            axisY: {
              showGrid: false,
            },
            lineSmooth: false,
          };
          this.component.set(
            <Chart
              type="line"
              title={title}
              data={timeData}
              options={chartOptions}
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
          let chartOptions = {
            axisY: {
              labelInterpolationFnc: function(value, index, labels) {
                let label = labels[index];

                if (label > 100000000) {
                  label = Math.round(label / 10000000).toString() + ' cr';
                } else if (label > 1000000) {
                  label = Math.round(label / 100000).toString() + ' L';
                } else if (label > 10000) {
                  label = Math.round(label / 1000).toString() + ' K';
                }

                if (details.column === 'base_amount') {
                  label = '₹' + label;
                } else if (details.agg_type === 'success_rate') {
                  label = '%' + label;
                }
                return label;
              },
            },
          };
          this.component.set(
            <Chart
              type="bar"
              title={title}
              data={barData}
              options={chartOptions}
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

import React, { Component } from 'react';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';

import { adminFetch } from 'common/fetch';
import { snakeToTitleCase } from 'common/util';

import Table from 'ui/Table';

export default class PublicFeaturesList extends Component {
  constructor(props) {
    super();
    this.state = {
      dashboards: [],
      isFetching: true,
      activeDashboardType: '',
      activeDashboardLabel: '',
      isFetchingDashboard: true,
      dashboardData: [],
      dashboardFields: [],
      selectDashboardValue: '',
    };
  }

  componentWillMount() {
    adminFetch(`live/admin/reports/types`).then(response => {
      this.setState({
        dashboards: response,
        isFetching: false,
      });
    });
  }

  createDynamicFields(data) {
    let fields = [];
    if (data[0]) {
      let obj = data[0];
      let keys = Object.keys(obj);
      keys.forEach(value =>
        fields.push([
          <span class="capitalize">{snakeToTitleCase(value)}</span>,
          item => item[value],
        ])
      );
    }
    return fields;
  }

  fetchDashboard(dashboardType) {
    let activeDashboard = this.state.dashboards.find(
      el => el['type'] == dashboardType
    );
    if (activeDashboard) {
      this.setState({
        activeDashboardType: dashboardType,
        activeDashboardLabel: activeDashboard['label'],
        isFetchingDashboard: true,
        dashboardData: [],
        dashboardFields: [],
      });

      adminFetch(`live/admin/reports/${dashboardType}`).then(response => {
        let newFields = this.createDynamicFields(response);
        this.setState({
          dashboardData: response,
          dashboardFields: newFields,
          isFetchingDashboard: false,
        });
      });
    } else {
      this.setState({
        activeDashboardType: dashboardType,
        dashboardData: [],
        dashboardFields: [],
      });
    }
  }

  onChange(e) {
    this.setState({
      selectDashboardValue: e.target.value,
    });
  }

  onSubmit(e) {
    this.fetchDashboard(this.state.selectDashboardValue);
  }

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Ops Dashboards</header>
          {this.state.isFetching ? (
            <div class="spinner center" />
          ) : (
            <Form class="filters" onSubmit={this.onSubmit.bind(this)}>
              <SelectField
                label="Select a dashboard"
                name="dashboard"
                onChange={this.onChange.bind(this)}
                value={this.state.selectDashboardValue}
              >
                <option value=""> None </option>
                {this.state.dashboards.map(item => (
                  <option value={item.type} key={item.type}>
                    {item.label}
                  </option>
                ))}
              </SelectField>

              <button class="btn pull-right">Go</button>
            </Form>
          )}
        </div>

        {this.state.activeDashboardType ? (
          <div class="box">
            <header>
              {this.state.activeDashboardLabel}({
                this.state.dashboardData.length
              })
            </header>
            {this.state.isFetchingDashboard ? (
              <div class="spinner center" />
            ) : (
              <Table
                items={this.state.dashboardData}
                fields={this.state.dashboardFields}
              />
            )}
          </div>
        ) : (
          ''
        )}
      </div>
    );
  }
}

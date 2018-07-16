import React, { Component } from 'react';

import Form from 'ui/Form';
import Field, { SelectField, SearchableSelectField } from 'ui/Field';

import { adminFetch } from 'common/fetch';
import { snakeToTitleCase } from 'common/util';

import Table from 'ui/Table';

import { notifyError } from 'common/modal';

export default class PublicFeaturesList extends Component {
  constructor(props) {
    super();
    this.state = {
      dashboards: [],
      isFetching: true,
      activeDashboardType: '',
      activeDashboardLabel: '',
      isFetchingDashboard: false,
      dashboardData: [],
      dashboardFields: []
    };
  }

  componentWillMount() {
    adminFetch(`live/admin/reports/types`).then(response => {
      if (response) {
        this.setState({
          dashboards: response,
          isFetching: false,
        });
      } else {
        notifyError(response.data.errors[0]);
      }
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
        if (response) {
          let newFields = this.createDynamicFields(response);
          this.setState({
            dashboardData: response,
            dashboardFields: newFields,
            isFetchingDashboard: false,
          });
        } else {
          notifyError(response.data.errors[0]);
        }
      });
    } else {
      this.setState({
        activeDashboardType: dashboardType,
        dashboardData: [],
        dashboardFields: [],
      });
    }
  }

  onSubmit({dashboard}) {
    this.fetchDashboard(dashboard);
  }

  render() {
    let defaultValue = this.state.dashboards[0] ? this.state.dashboards[0]['type'] : '',
    numberOfRows = this.state.dashboardData.length,
    resultsStr = numberOfRows == 1 ? `${numberOfRows} result` : `${numberOfRows} results`;

    return (
      <div class="list-container">
        <div class="box">
          <header>Ops Dashboard</header>
          {this.state.isFetching ? (
            <div class="spinner center" />
          ) : (
            <Form class="filters operation-reports" onSubmit={this.onSubmit.bind(this)}>
              <SearchableSelectField
                trackBy="value"
                label="Select a report"
                name="dashboard"
                defaultValue={defaultValue}
                options={this.state.dashboards.map(item => ({
                  name: item.label,
                  value: item.type,
                }))}
              />
              <button class="btn pull-right" disabled={this.state.isFetchingDashboard}>Go</button>
            </Form>
          )}
        </div>

        {this.state.activeDashboardType ? (
          <div class="box">
            <header>
              {this.state.activeDashboardLabel}({resultsStr})
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

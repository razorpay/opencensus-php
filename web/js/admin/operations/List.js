import React, { Component } from 'react';

import Form from 'ui/Form';
import { SearchableSelectField } from 'ui/Field';

import { adminFetch } from 'common/fetch';
import { snakeToTitleCase } from 'common/util';

import Table from 'ui/Table';

import { notifyError } from 'common/modal';

export default class PublicFeaturesList extends Component {
  constructor(props) {
    super();
    this.state = {
      reports: [],
      isLoading: true,
      activeReportType: '',
      activeTypeLabel: '',
      isLoadingData: false,
      data: [],
      fields: [],
    };

    this.onSubmit = this.onSubmit.bind(this);
  }

  componentWillMount() {
    adminFetch(`live/admin/reports/types`).then(response => {
      if (response) {
        this.setState({
          reports: response,
          isLoading: false,
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

  fetchDashboard(type) {
    let activeReport = this.state.reports.find(el => el['type'] === type);

    if (activeReport) {
      this.setState({
        activeReportType: type,
        activeTypeLabel: activeReport['label'],
        isLoadingData: true,
        data: [],
        fields: [],
      });

      adminFetch(`live/admin/reports/${type}`).then(response => {
        if (response) {
          let newFields = this.createDynamicFields(response);

          this.setState({
            data: response,
            fields: newFields,
            isLoadingData: false,
          });
        } else {
          notifyError(response.data.errors[0]);
        }
      });
    } else {
      this.setState({
        activeReportType: type,
        data: [],
        fields: [],
      });
    }
  }

  onSubmit({ report }) {
    this.fetchDashboard(report);
  }

  render() {
    let defaultValue = this.state.reports[0]
        ? this.state.reports[0]['type']
        : '',
      numberOfRows = this.state.data.length,
      resultsStr =
        numberOfRows === 1
          ? `${numberOfRows} result`
          : `${numberOfRows} results`;

    return (
      <div class="list-container">
        <div class="box">
          <header>Ops Dashboard</header>
          {this.state.isLoading ? (
            <div class="spinner center" />
          ) : (
            <Form class="filters operation-reports" onSubmit={this.onSubmit}>
              <SearchableSelectField
                trackBy="value"
                label="Select a report"
                name="report"
                defaultValue={defaultValue}
                options={this.state.reports.map(item => ({
                  name: item.label,
                  value: item.type,
                }))}
              />
              <button
                class="btn pull-right"
                disabled={this.state.isLoadingData}
              >
                Go
              </button>
            </Form>
          )}
        </div>

        {this.state.activeReportType ? (
          <div class="box">
            <header>
              {this.state.activeTypeLabel}({resultsStr})
            </header>
            {this.state.isLoadingData ? (
              <div class="spinner center" />
            ) : (
              <Table items={this.state.data} fields={this.state.fields} />
            )}
          </div>
        ) : (
          ''
        )}
      </div>
    );
  }
}

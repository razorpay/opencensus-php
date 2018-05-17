import React, { Component } from 'react';

export default class ReportLoader extends Component {
  render() {
    const { reportList } = this.props;

    return (
      <div>
        {Object.keys(reportList).map(config_id => (
          <div
            class="report-progress"
            key={config_id}
            style={{ margin: '20px 0' }}
          >
            {/* TODO: add report type */}
            Genrating Report
            <div class="bar-loader" />
            <small class="help-block">
              <i class="i i-info-circle" />
              This may take some time to download. You can also choose to{' '}
              <span
                class="btn-link"
                data-shouldupdate={true}
                onClick={this.props.openEmailReportModal}
              >
                Email this report.
              </span>
            </small>
          </div>
        ))}
      </div>
    );
  }
}

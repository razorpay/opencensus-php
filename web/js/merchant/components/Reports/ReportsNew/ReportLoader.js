import React, { Component } from 'react';
import { arrayToSentence } from 'rzp/utils/rzp-utils';

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
            Generating {reportList[config_id]['label']}
            <div class="bar-loader" />
            <small class="help-block">
              <i class="i i-info-circle" style={{ marginRight: '5px' }} />
              This may take some time to download.{' '}
              {reportList[config_id]['emails'] ? (
                <span>
                  We will also email this report to{' '}
                  {arrayToSentence(reportList[config_id]['emails'].split(','))}
                </span>
              ) : (
                <span>
                  You can also choose to{' '}
                  <span
                    class="btn-link"
                    onClick={this.props.openEmailReportModal}
                  >
                    Email this report.
                  </span>
                </span>
              )}
            </small>
          </div>
        ))}
      </div>
    );
  }
}

import React, { Component } from 'react';
import { arrayToSentence } from 'rzp/utils/rzp-utils';

const getReportText = reportName => {
  const isReportTextThere = reportName.toLowerCase().indexOf('report') > -1;
  return isReportTextThere ? reportName : `${reportName} Report`;
};
export default class ReportLoader extends Component {
  render() {
    const {
      selectedConfig,
      reportList,
      cancelDownload,
      configsLableMap,
    } = this.props;

    return (
      <div class="report-loader">
        <hr />
        {Object.keys(reportList).map(config_id => (
          <div
            class={`report-progress ${
              selectedConfig === config_id ? 'current' : ''
            }`}
            key={config_id}
          >
            {/* TODO: add report type */}
            Generating {getReportText(configsLableMap[config_id])} ...
            <div>
              <div class="bar-loader" />
              <span
                class="report-close"
                onClick={() => cancelDownload(config_id)}
              >
                <i class="i i-close" />
              </span>
            </div>
            <small class="help-block">
              <i class="i i-info-circle" />
              This may take some time to download.{' '}
              {reportList[config_id]['emails'] ? (
                <span>
                  We will also email this report to{' '}
                  {arrayToSentence(reportList[config_id]['emails'])}
                </span>
              ) : (
                <span>
                  You can also choose to{' '}
                  <span
                    class="btn-link"
                    data-configid={config_id}
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

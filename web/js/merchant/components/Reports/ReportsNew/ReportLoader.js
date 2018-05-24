import React, { Component } from 'react';
import { arrayToSentence } from 'rzp/utils/rzp-utils';

const ReportHelperText = ({ id, emails, status, openEmailReportModal }) => {
  return (
    <small class={`help-block ${status}`}>
      <i class="i i-info-circle" />
      {status === 'pending' ? (
        <span>
          This may take some time to download.{' '}
          {emails ? (
            <span>
              We will also email this report to {arrayToSentence(emails)}
            </span>
          ) : (
            <span>
              You can also choose to{' '}
              <span
                class="btn-link"
                data-reportid={id}
                onClick={openEmailReportModal}
              >
                Email this report.
              </span>
            </span>
          )}
        </span>
      ) : (
        <span>
          {status === 'success'
            ? `Report downloaded successfully.`
            : `There was an error while generating this report.`}
        </span>
      )}
    </small>
  );
};

const ReportProgress = ({
  id,
  name,
  status,
  children,
  isSelected,
  cancelDownload,
}) => {
  return (
    <div class={`report-progress ${isSelected ? 'current' : ''}`}>
      Generating {name} ...
      <div>
        <div class={`bar-loader ${status}`} />
        <span class="report-close" onClick={() => cancelDownload(id)}>
          <i class="i i-close" />
        </span>
      </div>
      {children}
    </div>
  );
};

export default class ReportLoader extends Component {
  getReportText = reportName => {
    const isReportTextThere = reportName.toLowerCase().indexOf('report') > -1;
    return isReportTextThere ? reportName : `${reportName} Report`;
  };

  getReportStatus = reportId => {
    const { reportList } = this.props;
    let reportStatus = 'pending';

    switch (reportList[reportId]['status']) {
      case 'failed':
        reportStatus = 'failed';
        break;
      case 'created':
        reportStatus = 'pending';
        break;
      case 'processed':
        if (reportList[reportId]['file_id']) {
          reportStatus = 'success';
        } else {
          reportStatus = 'failed';
        }
        break;
      default:
        reportStatus = 'failed';
        break;
    }
    return reportStatus;
  };

  render() {
    const { selectedConfigId, reportList, configsLableMap } = this.props;

    return (
      <div class="report-loader">
        <hr />
        {Object.keys(reportList).map(reportId => (
          <ReportProgress
            id={reportId}
            key={reportId}
            status={this.getReportStatus(reportId)}
            isSelected={selectedConfigId === reportList[reportId]['config_id']}
            name={this.getReportText(
              configsLableMap[reportList[reportId]['config_id']]
            )}
            cancelDownload={this.props.cancelDownload}
          >
            <ReportHelperText
              id={reportId}
              emails={reportList[reportId]['emails']}
              status={this.getReportStatus(reportId)}
              openEmailReportModal={this.props.openEmailReportModal}
            />
          </ReportProgress>
        ))}
      </div>
    );
  }
}

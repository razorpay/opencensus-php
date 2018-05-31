import React, { Component } from 'react';
import { arrayToSentence } from 'rzp/utils/rzp-utils';

const ReportHelperText = ({ report, status, openEmailReportModal }) => {
  const isNoDataFound = report['status'] === 'processed' && !report['file_id'];

  const successMsg = 'Report downloaded successfully.',
    failureMsg = isNoDataFound
      ? 'No data found!'
      : 'There was an error while generating this report.';

  return (
    <small class={`help-block ${status}`}>
      <i class="i i-info-circle" />
      {status === 'pending' ? (
        <span>
          This may take some time to download.{' '}
          {report.emails ? (
            <span>
              We will also email this report to {arrayToSentence(report.emails)}
            </span>
          ) : (
            <span>
              You can also choose to{' '}
              <span
                class="btn-link"
                data-reportid={report.id}
                onClick={openEmailReportModal}
              >
                Email this report.
              </span>
            </span>
          )}
        </span>
      ) : (
        <span>{status === 'success' ? successMsg : failureMsg}</span>
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
    let reportStatus = reportList[reportId]['status'];

    if (reportStatus === 'created') {
      return 'pending';
    }

    if (reportStatus === 'processed' && reportList[reportId]['file_id']) {
      return 'success';
    }

    return 'failed';
  };

  //- return selected config's reports first
  sortReportLoaderList = reportsKeysList => {
    const { selectedConfigId, reportList } = this.props;

    return (reportsKeysList = reportsKeysList.sort((a, b) => {
      if (
        reportList[a]['config_id'] === selectedConfigId &&
        reportList[b]['config_id'] !== selectedConfigId
      ) {
        return -1;
      } else {
        return 1;
      }
    }));
  };

  render() {
    const { selectedConfigId, reportList, configsLableMap } = this.props;
    let reportsKeysList = Object.keys(reportList);

    reportsKeysList = this.sortReportLoaderList(reportsKeysList);

    return (
      <div class="report-loader">
        {reportsKeysList.length > 0 && <hr />}
        {reportsKeysList.map(reportId => (
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
              report={reportList[reportId]}
              status={this.getReportStatus(reportId)}
              openEmailReportModal={this.props.openEmailReportModal}
            />
          </ReportProgress>
        ))}
      </div>
    );
  }
}

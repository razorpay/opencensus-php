import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import {
  getFormattedDate,
  extractExtensionFromTemplate,
  isLogInProgress,
  getActualLogStatus,
} from 'merchant_common/containers/ReportsAsync/utils';
import KindOfLog from 'merchant_common/containers/ReportsAsync/Logs/components/KindOfLog';
import LogStatus from 'merchant_common/containers/ReportsAsync/Logs/components/LogStatus';
import React from 'react';

export const DEFAULT_FILE_FORMAT = 'csv';

export const logItemInfoMessages = {
  'in-process':
    'Report generation might take  anywhere between 2 min - 1 hour depending on the data volume. You can download here when report is ready.',
  'no-data': 'Report could not be generated as there is no data available.',
  'ready-for-download': 'Report has been successfully generated',
  error: 'Something went wrong, please try again after sometime.',
};

export default class LogItem extends React.PureComponent {
  componentDidMount() {
    const { status, id, consumer, generated_by } = this.props;
    const accountId = consumer !== generated_by ? consumer : undefined;
    if (isLogInProgress(status)) {
      this.props.pollLog(id, accountId, ({ abort }) => {
        this.abortPolling = abort;
      });
    }
  }

  render() {
    const { config, ...props } = this.props;
    const actualStatus = getActualLogStatus({
      status: props.status,
      fileId: props.file_id,
    });

    return (
      <div className={classList('LogItem', `LogItem--${actualStatus}`, props.isNew && 'LogItem--new')}>
        <div className="LogItem__Body">
          <div>
            <strong>{config.name || '--'}</strong>
            <ReportDuration startTime={props.start_time} endTime={props.end_time} />
          </div>

          <div>
            <FileFormat logTemplate={props.template_overrides} configTemplate={config.template} />
          </div>

          <KindOfLog scheduleId={props.schedule_id} createdAt={props.created_at} />

          <LogStatus
            actualStatus={actualStatus}
            onDownloadClick={(e) => {
              const format =
                extractExtensionFromTemplate(props.template_overrides) ||
                extractExtensionFromTemplate(config.template) ||
                DEFAULT_FILE_FORMAT;
              analyticsTrack({
                objectName: 'download report',
                actionName: 'clicked',
                screen: 'reports',
                properties: {
                  location: 'generate reports',
                  reportType: config.name,
                  format,
                  reportStartTime: props.start_time,
                  reportEndTime: props.end_time,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              props.onDownloadClick(e, config.name, format, props.start_time, props.end_time);
            }}
            consumerId={props.consumer}
            fileId={props.file_id}
          />
        </div>
        {!!logItemInfoMessages[actualStatus] && (
          <div
            className={classList(
              'LogItem__InfoBar',
              'text-muted',
              'text-small',
              `LogItem__InfoBar--${actualStatus}`,
            )}
            data-testid="log-item-info-message"
          >
            <i className="i i-info-outline" /> {logItemInfoMessages[actualStatus]}
          </div>
        )}
      </div>
    );
  }

  componentWillUnmount() {
    if (this.abortPolling) {
      this.abortPolling();
    }
  }
}

function ReportDuration({ startTime, endTime }) {
  const startDate = getFormattedDate(startTime);
  const endDate = getFormattedDate(endTime);
  return (
    <p className="text-muted small" data-testid="report-duration">
      ({startDate} {startDate !== endDate ? `- ${endDate}` : ''})
    </p>
  );
}

function FileFormat({ logTemplate, configTemplate }) {
  return (
    <>
      <strong>Format</strong>
      <p className="text-muted text-small" data-testid="file-format">
        {(
          extractExtensionFromTemplate(logTemplate) ||
          extractExtensionFromTemplate(configTemplate) ||
          DEFAULT_FILE_FORMAT
        ).toUpperCase()}
      </p>
    </>
  );
}

import { classList } from 'common/utils/rzp-utils';

import {
  getFormattedDate,
  extractExtensionFromTemplate,
  isLogInProgress,
  getActualLogStatus,
} from '../utils';
import KindOfLog from './components/KindOfLog';
import LogStatus from './components/LogStatus';

const DEFAULT_FILE_FORMAT = 'csv';

export default class LogItem extends React.PureComponent {
  componentDidMount() {
    const { status, id } = this.props;
    if (isLogInProgress(status)) {
      this.props.pollLog(id);
    }
  }

  render() {
    const { config, ...props } = this.props;
    const actualStatus = getActualLogStatus({
      status: props.status,
      fileId: props.file_id,
    });

    return (
      <div
        class={classList(
          'LogItem',
          `LogItem--${actualStatus}`,
          props.isNew && 'LogItem--new'
        )}
      >
        <div className="LogItem__Body">
          <div>
            <strong>{config.name || '--'}</strong>
            <ReportDuration
              startTime={props.start_time}
              endTime={props.end_time}
            />
          </div>

          <div>
            <FileFormat
              logTemplate={props.template_overrides}
              configTemplate={config.template}
            />
          </div>

          <KindOfLog
            scheduleId={props.schedule_id}
            createdAt={props.created_at}
          />

          <LogStatus
            actualStatus={actualStatus}
            onDownloadClick={props.onDownloadClick}
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
              `LogItem__InfoBar--${actualStatus}`
            )}
          >
            <i class="i i-info-outline" /> {logItemInfoMessages[actualStatus]}
          </div>
        )}
      </div>
    );
  }
}

function ReportDuration({ startTime, endTime }) {
  const startDate = getFormattedDate(startTime);
  const endDate = getFormattedDate(endTime);
  return (
    <p class="text-muted small">
      ({startDate} {startDate !== endDate ? `- ${endDate}` : ''})
    </p>
  );
}

function FileFormat({ logTemplate, configTemplate }) {
  return (
    <>
      <strong>Format</strong>
      <p class="text-muted text-small">
        {(
          extractExtensionFromTemplate(logTemplate) ||
          extractExtensionFromTemplate(configTemplate) ||
          DEFAULT_FILE_FORMAT
        ).toUpperCase()}
      </p>
    </>
  );
}

const logItemInfoMessages = {
  created:
    'Report is getting generated. At certain cases it might take a little longer to generate.',
  'no-data':
    'Report could not be generated as there is no data data available.',
  'ready-for-download': 'Report has been successfully generated',
  error: 'Something went wrong, please try again after sometime.',
};

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
            <p>{config.name || '--'}</p>

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
          />
        </div>
        <div
          className={classList(
            'LogItem__InfoBar',
            'text-muted',
            'text-small',
            `LogItem__InfoBar--${actualStatus}`
          )}
        >
          <i class="i i-info-outline" />{' '}
          {getLogItemInfoMessage({ actualStatus })}
        </div>
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
      <label>Format</label>
      <p class="text-muted">
        {extractExtensionFromTemplate(logTemplate) ||
          extractExtensionFromTemplate(configTemplate) ||
          DEFAULT_FILE_FORMAT}
      </p>
    </>
  );
}

function getLogItemInfoMessage({ actualStatus }) {
  switch (actualStatus) {
    case 'created':
      return 'Report is getting generated. At certain cases it might take a little longer to generate.';
    case 'no-data':
      return 'Report could not be generated as there is no data data available.';
    case 'ready-for-download':
      return 'Report has been successfully generated';
    case 'error':
      return 'Something went wrong, please try again after sometime.';
    default:
      return null;
  }
}

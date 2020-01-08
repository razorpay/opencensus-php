import { classList } from 'common/utils/rzp-utils';

import {
  getFormattedDate,
  extractExtensionFromTemplate,
  isLogInProgress,
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
    return (
      <div class={classList('LogItem', `LogItem--${props.status}`)}>
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
            status={props.status}
            fileId={props.file_id}
            onDownloadClick={props.onDownloadClick}
            consumerId={props.consumer}
          />
        </div>
        <div className="LogItem__InfoBar">{props.info}</div>
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

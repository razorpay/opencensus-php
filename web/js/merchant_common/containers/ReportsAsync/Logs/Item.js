import { classList } from 'common/utils/rzp-utils';

import { getFormattedDate } from '../utils';
import KindOfLog from './components/KindOfLog';
import LogStatus from './components/LogStatus';

const DEFAULT_FILE_FORMAT = 'csv';

export default function LogItem(props) {
  const startDate = getFormattedDate(props.start_time);
  const endDate = getFormattedDate(props.end_time);

  return (
    <div class={classList('LogItem', `LogItem--${props.status}`)}>
      <div className="LogItem__Body">
        <div>
          <p>
            <strong>{props.configName}</strong>
          </p>
          <p class="text-muted">
            ({startDate} - {endDate})
          </p>
        </div>
        <div>
          <label>Format</label>
          <p class="text-muted">
            {getFileFormat({
              logTemplate: props.template_overrides,
              configTemplate: props.configTemplate,
            }).toUpperCase()}
          </p>
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

function getFileFormat({ logTemplate, configTemplate }) {
  const extension =
    extractExtension(logTemplate) || extractExtension(configTemplate);
  return extension || DEFAULT_FILE_FORMAT;
}

function extractExtension(template) {
  return ((template || {}).file_meta || {} || {}).extension;
}

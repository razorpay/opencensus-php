import { classList } from 'common/utils/rzp-utils';

import { getFormattedDate } from './utils';
import KindOfLog from './components/KindOfLog';
import LogStatus from './components/LogStatus';

export default function LogItem(props) {
  const startDate = getFormattedDate(props.start_time);
  const endDate = getFormattedDate(props.end_time);
  return (
    <div class={classList('LogItem', `LogItem--${props.status}`)}>
      <div className="LogItem__Body">
        <div>
          <p>
            <strong>{props.name}</strong>
          </p>
          <p class="text-muted">
            ({startDate} - {endDate})
          </p>
        </div>
        <div>
          <label>Format</label>
          <p class="text-muted">{getFileFormat(props)}</p>
        </div>

        <KindOfLog
          scheduleId={props.schedule_id}
          createdAt={props.created_at}
        />

        <LogStatus {...props} />
      </div>
      <div className="LogItem__InfoBar">{props.info}</div>
    </div>
  );
}

function getFileFormat({ template_overrides: templateOverride }) {
  const extension = ((templateOverride || {}).file_meta | {}).extension;
  return extension || '--';
}

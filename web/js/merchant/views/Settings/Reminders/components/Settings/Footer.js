import { AsyncBtn } from 'common/new-ui/Button';

export default ({ isSaveBtnDisable, onSaveClick, scheduledTime }) => (
  <div className="ReminderSettings-Footer">
    <div className="content">
      Note: Reminders are sent only between {scheduledTime}. If required, you can turn a reminder on
      or off for specific customer.{' '}
    </div>
    <AsyncBtn.Primary disabled={isSaveBtnDisable} onClick={onSaveClick}>
      Save Changes
    </AsyncBtn.Primary>
  </div>
);

import { AsyncBtn } from 'component/Button';

export default ({ isSaveBtnDisable, onSaveClick, scheduledTime }) => (
  <div class="Reminders-settings__footer">
    <div class="content">
      Reminders will be sent to customers between {scheduledTime}. You can turn
      ON/OFF reminders for any individual customer.{' '}
    </div>

    <AsyncBtn.Primary disabled={isSaveBtnDisable} onClick={onSaveClick}>
      Save Changes
    </AsyncBtn.Primary>
  </div>
);

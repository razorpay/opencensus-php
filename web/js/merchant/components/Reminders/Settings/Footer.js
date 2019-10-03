import Button, { AsyncBtn } from 'component/Button';

export default ({
  isSaveBtnDisable,
  onSaveClick,
  scheduledTime,
  onPreviewClick,
}) => (
  <div class="Reminders-settings__footer">
    <div class="content">
      Reminders will be sent to customers between {scheduledTime}. You can turn
      ON/OFF reminders for any individual customer.{' '}
      <Button.Transparent onClick={onPreviewClick}>
        See a preview
      </Button.Transparent>
    </div>

    <AsyncBtn.Primary disabled={isSaveBtnDisable} onClick={onSaveClick}>
      Save Changes
    </AsyncBtn.Primary>
  </div>
);

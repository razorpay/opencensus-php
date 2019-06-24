import Button from 'component/Button';

export default ({ onSaveClick, reminderTime, onPreviewClick }) => (
  <div class="reminders-setting_footer">
    <div class="description">
      Reminders will be sent to customers between {reminderTime}. You can turn
      ON/OFF reminders for any individual customer.{' '}
      <Button.Transparent onClick={onPreviewClick}>
        See a preview
      </Button.Transparent>
    </div>

    <Button.Primary onClick={onSaveClick}>Save Changes</Button.Primary>
  </div>
);

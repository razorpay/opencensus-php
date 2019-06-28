import Button from 'component/Button';

export default ({ onSaveClick, scheduledTime, onPreviewClick }) => (
  <div class="reminders-setting_footer">
    <div class="content">
      Reminders will be sent to customers between {scheduledTime}. You can turn
      ON/OFF reminders for any individual customer.{' '}
      <Button.Transparent onClick={onPreviewClick}>
        See a preview
      </Button.Transparent>
    </div>

    <Button.Primary onClick={onSaveClick}>Save Changes</Button.Primary>
  </div>
);

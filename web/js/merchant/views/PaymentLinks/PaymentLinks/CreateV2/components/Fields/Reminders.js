import { Link } from 'react-router-dom';
import Input from 'common/new-ui/Input';
import track from '../../track';

const Reminders = ({ config, hasNoExpiry, ...extraProps }) => {
  const isEnabled =
    config.isEnabled &&
    (config.configs_count.without_expiry > 0 || config.configs_count.with_expiry > 0);
  if (!isEnabled) {
    const type = hasNoExpiry && 'no';
    return (
      <div class="Input Input--vTop">
        <div class="Input-label">Reminders</div>
        <div class="Input-content">
          Reminders is not set to payment links with {type} expiry date.
          <br />
          Set it up{' '}
          <Link target="_blank" to="/reminders" rel="noreferrer noopener">
            here
          </Link>
        </div>
      </div>
    );
  }

  const description = getDescription(config.configs_count, hasNoExpiry);
  return (
    <Input.Check
      autoRender
      name="reminder_enable"
      fieldLabel="Send auto reminders"
      label="Reminders"
      description={description}
      class="Input--vTop"
      labelClass="Input-label pb-8"
      onBlur={() => {
        track.lj.fields.reminders();
        track.segment.fields.reminders();
      }}
      {...extraProps}
    />
  );
};

function getDescription(count, hasNoExpiry) {
  const totalReminders = hasNoExpiry ? count.without_expiry : count.with_expiry;

  return `${totalReminders} auto reminders will be sent to this customer based on the reminder settings`;
}

export default Reminders;

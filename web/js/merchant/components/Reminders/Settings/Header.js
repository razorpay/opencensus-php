import SwitchField from 'common/ui/Forms/SwitchField';

export default ({ disabled, type, isEnabled, onToggle }) => (
  <React.Fragment>
    <span className="title">Reminders for {type}</span>

    <SwitchField
      class="m-l"
      type="prime"
      checked={isEnabled}
      disabled={disabled}
      onChange={onToggle}
    />
    <span class="status-text">{isEnabled ? 'Enabled' : 'Disabled'}</span>

    <p class="description">
      Send automated reminders to unpaid {type} and get paid on time.
    </p>
  </React.Fragment>
);

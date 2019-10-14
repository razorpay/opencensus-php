import SwitchField from 'rzp/ui/Forms/SwitchField';

export default ({ disabled, type, checked, onToggle }) => (
  <React.Fragment>
    <span className="title">Reminders for {type}</span>

    <SwitchField
      class="m-l"
      type="prime"
      checked={checked}
      disabled={disabled}
      onChange={onToggle}
    />
    <span class="status-text">{checked ? 'Enabled' : 'Disabled'}</span>

    <p class="description">
      Send automated reminders to unpaid {type} and get paid on time.
    </p>
  </React.Fragment>
);

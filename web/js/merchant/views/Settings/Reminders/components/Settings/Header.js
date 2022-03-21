import React from 'react';
import SwitchField from 'common/ui/Forms/SwitchField';

export default ({ disabled, type, isEnabled, onToggle }) => (
  <React.Fragment>
    <span className="title">
      {type === 'Payment Links' ? `${type} reminders` : `Reminders for ${type}`}
    </span>

    <span className="enable-wrapper">
      <SwitchField
        class="m-l"
        type="prime"
        checked={isEnabled}
        disabled={disabled}
        onChange={onToggle}
      />
      <span className="status-text">{isEnabled ? 'Enabled' : 'Disabled'}</span>
    </span>

    <p className="description">Send automated reminders for unpaid {type} and get paid on time.</p>
  </React.Fragment>
);

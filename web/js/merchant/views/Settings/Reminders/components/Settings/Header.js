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
      <span className="status-text">{isEnabled ? 'On' : 'Off'}</span>
    </span>

    <p className="description">
      Send collection reminders to customers automatically if a{' '}
      {type === 'Payment Links' ? 'payment link' : type} hasnt been paid.
    </p>
  </React.Fragment>
);

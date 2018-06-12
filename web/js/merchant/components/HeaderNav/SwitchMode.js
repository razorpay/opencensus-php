import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        <span
          class={`ModeIndicator ${
            mode === 'test' ? 'ModeIndicator--test' : 'ModeIndicator--live'
          }`}
        />{' '}
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          <li>
            <a onClick={() => onSwitchMode('test')}>Test Mode</a>
          </li>
          <li>
            <a onClick={() => onSwitchMode('live')}>Live Mode</a>
          </li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default SwitchMode;

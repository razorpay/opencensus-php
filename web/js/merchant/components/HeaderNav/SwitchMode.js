import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList } from 'common/utils/rzp-utils';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle switch-modes-toggle">
        <span
          class={`ModeIndicator ${
            mode === 'test' ? 'ModeIndicator--test' : 'ModeIndicator--live'
          }`}
        />{' '}
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu switch-modes-menu nav nav-stacked">
          <li>
            <a
              onClick={() => onSwitchMode('test')}
              class={classList(mode === 'test' && 'selected')}
            >
              <span class="ModeIndicator ModeIndicator--test" /> Test Mode
            </a>
          </li>
          <li>
            <a
              onClick={() => onSwitchMode('live')}
              class={classList(mode === 'live' && 'selected')}
            >
              <span class="ModeIndicator ModeIndicator--live" /> Live Mode
            </a>
          </li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default SwitchMode;

import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { classList } from 'common/utils/rzp-utils';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle switch-modes-toggle">
        {mode === 'test' ? (
          <small class="help-content">
            <i class="i i-info-circle ModeIndicator--test" />
            <Popover align="bottom" theme="dark" className="test-mode-popover">
              <PopoverBody>
                <div>All the transactions that are done in Test mode are</div>
                <div>sample transactions and there will not be any real money</div>
                <div>debited/credited in your account.</div>
              </PopoverBody>
            </Popover>
          </small>
        ) : (
          <span class="ModeIndicator ModeIndicator--live" />
        )}{' '}
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu switch-modes-menu nav nav-stacked">
          <li>
            <a
              onClick={() => onSwitchMode('test')}
              class={classList(mode === 'test' && 'selected')}
            >
              <small class="help-content">
                <i class="i i-info-circle ModeIndicator--test" />
                <Popover align="bottom" theme="dark" className="test-mode-popover">
                  <PopoverBody>
                    <div>All the transactions that are done in Test mode are</div>
                    <div>sample transactions and there will not be any real money</div>
                    <div>debited/credited in your account.</div>
                  </PopoverBody>
                </Popover>
              </small>{' '}
              Test Mode
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

import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode, isTestModeBlocked }) => {
  const dropdownDisabled = mode === 'live' && !!isTestModeBlocked;

  return (
    <Dropdown disabled={dropdownDisabled}>
      <DropdownTrigger class="dropdown-toggle switch-modes-toggle">
        <div
          onClick={() => {
            analyticsTrack({
              objectName: 'modes dropdown',
              actionName: 'clicked',
              screen: 'home page',
              properties: {
                current: mode,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
          }}
        >
          {mode === 'test' ? (
            <>
              <i class="i i-info-circle ModeIndicator--test" /> {modeFormatted} Mode
            </>
          ) : (
            <>
              <i class="i i-done ModeIndicator--live-icon" /> {modeFormatted} Mode
            </>
          )}{' '}
          {!dropdownDisabled && <span class="caret" />}
        </div>
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu switch-modes-menu nav nav-stacked">
          <li data-test="Test Mode">
            <a
              onClick={() => onSwitchMode('test')}
              class={classList(mode === 'test' && 'selected')}
            >
              <i class="i i-info-circle ModeIndicator--test" /> Test Mode
            </a>
          </li>
          <li data-test="Live Mode">
            <a
              onClick={() => onSwitchMode('live')}
              class={classList(mode === 'live' && 'selected')}
            >
              <i class="i i-done ModeIndicator--live-icon" /> Live Mode
            </a>
          </li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default SwitchMode;

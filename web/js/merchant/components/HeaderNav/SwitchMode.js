import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode, isTestModeBlocked, user }) => {
  const dropdownDisabled = mode === 'live' && !!isTestModeBlocked;

  const isActivated = localStorage.getItem(`is_activated--${user.current}`);
  const isLiveModeActivatedKeySet =
    mode === 'live' &&
    !isActivated &&
    (user.activation_status === 'activated' ||
      user.activation_status === 'activated_mcc_pending' ||
      (user.isUnregisteredBusiness &&
        user.activation_form_milestone === 'L1' &&
        user.poi_verification_status === 'verified' &&
        user.activation_status === 'instantly_activated'));

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
              onClick={() => {
                onSwitchMode('test');
                if (isLiveModeActivatedKeySet) {
                  localStorage.setItem(`is_activated--${user.current}`, 'true');
                }
              }}
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

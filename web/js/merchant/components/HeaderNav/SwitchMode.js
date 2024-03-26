import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { Box, ChevronDownIcon, Text } from '@razorpay/blade/components';
import { ModesDropdownWrapper } from './styled';

const SwitchMode = ({ mode, modeFormatted, onSwitchMode, isTestModeBlocked, isRTUXHomepage }) => {
  const dropdownDisabled = mode === 'live' && !!isTestModeBlocked;
  const isTestMode = mode === 'test';
  return (
    <Dropdown disabled={dropdownDisabled}>
      <DropdownTrigger class="dropdown-toggle switch-modes-toggle">
        {isRTUXHomepage ? (
          <ModesDropdownWrapper
            onClick={() => {
              analyticsTrack({
                objectName: 'Modes Dropdown',
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  current: mode,
                  version: 'v2',
                  ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
                },
              });
            }}
          >
            <Box display="flex" alignItems="center" justifyContent="center" marginTop="spacing.2">
              <i
                class={
                  isTestMode
                    ? 'i i-info-circle ModeIndicator--test'
                    : 'i i-done ModeIndicator--live-icon'
                }
              />
            </Box>
            <Text weight="bold" color="surface.text.subtle.lowContrast">
              {modeFormatted} Mode
            </Text>
            <ChevronDownIcon size="medium" color="feedback.icon.neutral.lowContrast" />
          </ModesDropdownWrapper>
        ) : (
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
            {isTestMode ? (
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
        )}
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu switch-modes-menu nav nav-stacked">
          <li data-test="Test Mode">
            <a onClick={() => onSwitchMode('test')} class={classList(isTestMode && 'selected')}>
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

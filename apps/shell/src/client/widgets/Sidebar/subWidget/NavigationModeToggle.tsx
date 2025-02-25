// Mode toggle especially used in navigation footer.
import React, { useState } from 'react';
import { Indicator, SideNavItem, Switch } from '@razorpay/blade/components';

const NavigationModeToggle = ({ one_nav_config: { initial_value, is_disabled } }) => {
  const [isTestMode, setIsTestMode] = useState(initial_value === 'test');

  return (
    <SideNavItem
      as="label"
      title="Test Mode"
      leading={
        <Indicator
          color={isTestMode ? 'notice' : 'positive'}
          emphasis="intense"
          accessibilityLabel=""
        />
      }
      backgroundColor={isTestMode ? `feedback.background.notice.subtle` : undefined}
      trailing={
        <Switch
          isDisabled={is_disabled}
          accessibilityLabel=""
          size="small"
          isChecked={isTestMode}
          onChange={({ isChecked }) => {
            setIsTestMode(isChecked);
          }}
        />
      }
    />
  );
};

export { NavigationModeToggle };

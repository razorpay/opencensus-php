import { Indicator, SideNavItem, Switch } from '@razorpay/blade/components';
import useConnectedNavigationStore from 'merchant/components/NavigationLayout/navigationStore';
import React, { useState } from 'react';
import { useStore } from 'shell/commonStore';
import { useNavigationLayoutContext } from '../../context';

// 1. READ payment Mode
// 2. Read partner Mode
// 3. Toggle payment mode
// 4. Toggle partner mode
// 5. Add the toast
// 6. Remove existing toast

interface SidebarModeSwitcherProps {
  mode: 'test' | 'live';
}

function SidebarModeSwitcher({ mode }: SidebarModeSwitcherProps): JSX.Element | null {
  const { onSwitchMode } = useNavigationLayoutContext();
  const [currentMode, setCurrentMode] = useState(mode);
  const isTestModeActive = currentMode === 'test';

  const handleSwitchMode = () => {
    const invertMode = mode === 'live' ? 'test' : 'live';
    setCurrentMode(invertMode);
    onSwitchMode(invertMode);
  };

  return (
    <SideNavItem
      as="label"
      title="Test Mode"
      leading={
        <Indicator
          color={isTestModeActive ? 'notice' : 'positive'}
          emphasis="intense"
          accessibilityLabel=""
        />
      }
      backgroundColor={isTestModeActive ? 'feedback.background.notice.subtle' : undefined}
      trailing={
        <Switch
          accessibilityLabel=""
          size="small"
          isChecked={isTestModeActive}
          onChange={handleSwitchMode}
          isDisabled={currentMode !== mode}
        />
      }
    />
  );
}

export default SidebarModeSwitcher;

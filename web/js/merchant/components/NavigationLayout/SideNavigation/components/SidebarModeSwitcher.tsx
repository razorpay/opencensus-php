import { Indicator, SideNavItem, Switch } from '@razorpay/blade/components';
import useConnectedNavigationStore from 'merchant/components/NavigationLayout/navigationStore';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import React, { useState } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { useNavigationLayoutContext } from '../../context';
import { ANALYTICS_ONENAV } from '@libs/shared-utils';

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
  const { selectedProduct } = useConnectedNavigationStore();
  const [currentMode, setCurrentMode] = useState(mode);
  const isTestModeActive = currentMode === 'test';

  const handleSwitchMode = () => {
    const invertMode = mode === 'live' ? 'test' : 'live';
    let page = location.pathname?.replace('/app/', '');
    setCurrentMode(invertMode);
    onSwitchMode(invertMode);
    analyticsTrack({
      objectName: 'Sidebar',
      actionName: 'Clicked',
      screen: ANALYTICS_ONENAV.SCREEN,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        version: 'v1',
        option_name: 'Mode Switcher',
        page,
        section: 'Mode and Settings',
        bu_title: selectedProduct.product?.title,
        ToggleModeTo: invertMode,
        ToggleModeFrom: mode,
        experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
      },
    });
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

import React, { useEffect } from 'react';
import { useTheme } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../Navigation/hooks';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';

/**
 * ThemeSwitcher component that changes the application theme based on the active product
 * This component doesn't render anything visible
 */
export const ThemeSwitcher: React.FC = () => {
  const { setColorScheme } = useTheme();
  const { isBankingActive } = useGetActiveProduct();
  const currentActionType = useConnectedNavigationStore(
    (state) => state.products.selectedProduct?.selectAction?.actionType,
  );

  useEffect(() => {
    if (isBankingActive) {
      // exclude access_denied_page and growth_page from dark theme
      // todo: everything should be eventually set to light theme
      if (currentActionType && !['access_denied_page', 'growth_page'].includes(currentActionType)) {
        setColorScheme('dark');
      } else {
        setColorScheme('light');
      }
    } else {
      setColorScheme('light');
    }
  }, [isBankingActive, setColorScheme, currentActionType]);

  return null;
};

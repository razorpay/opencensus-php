import React, { useEffect } from 'react';
import { useTheme } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../Navigation/hooks';

/**
 * ThemeSwitcher component that changes the application theme based on the active product
 * This component doesn't render anything visible
 */
export const ThemeSwitcher: React.FC = () => {
  const { setColorScheme } = useTheme();
  const { isBankingActive } = useGetActiveProduct();

  // TODO: Introduce this when launching X in connected dashboard
  // useEffect(() => {
  //   if (isBankingActive) {
  //     setColorScheme('dark');
  //   } else {
  //     setColorScheme('light');
  //   }
  // }, [isBankingActive, setColorScheme]);

  return null;
};

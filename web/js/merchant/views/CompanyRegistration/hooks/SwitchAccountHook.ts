import { useState } from 'react';
import { logoutUser } from 'newAuth/@commander-shield/api/signinApi';
import { MULTI_ACCOUNT } from '../constant';
import { trackEventOnCreateAccountCtaClick } from '../analytics';
/**
 * A custom hook to manage user interactions for multi-account handling in authentication flows.
 * 
 * Features:
 * - Manages which account type ('primary' or 'secondary') the user has selected.
 * - Handles user action based on selection:
 *    - If 'primary' is selected: opens the multi-account creation link in a new tab.
 *    - If 'secondary' is selected: logs the user out and reloads the window.
 * - Tracks analytics event when the user initiates the account creation flow.
 */
type SelectedType = 'primary' | 'secondary';

export const useMultiAccount = () => {
  const [selected, setSelected] = useState<SelectedType>('primary');
  const [isLoading, setIsLoading] = useState(false);
  const handleLogout = () => {
    setIsLoading(true);
    logoutUser()
      .then(() => {
        window.location.reload();
      })
      .finally(() => {
        setIsLoading(false);
      });
  };
  const handleUserAction = () => {
    trackEventOnCreateAccountCtaClick();
    if (selected === 'primary') {
      window.open(MULTI_ACCOUNT, '_blank', 'noopener,noreferrer');
    } else {
      handleLogout();
    }
  };
  return {
    selected,
    setSelected,
    isLoading,
    handleUserAction,
  };
};

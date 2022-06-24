import { useEffect, useState } from 'react';
import {
  VIEWS,
  COMPONENTS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

export const Settings = ({ settings, platform }) => {
  const [view, setView] = useState(VIEWS.EDIT);

  const switchToEdit = () => setView(VIEWS.EDIT);

  useEffect(() => {
    if (
      settings.status === FETCH_STATUS.IDLE &&
      settings.has_saved_config &&
      settings.platform === platform
    ) {
      setView(VIEWS.READ);
    } else {
      setView(VIEWS.EDIT);
    }
  }, [settings, platform]);

  if (!platform) {
    return null;
  }

  if (view === VIEWS.EDIT) {
    return COMPONENTS[platform].formComponent();
  }
  return COMPONENTS[platform].cardComponent(switchToEdit);
};

export default Settings;

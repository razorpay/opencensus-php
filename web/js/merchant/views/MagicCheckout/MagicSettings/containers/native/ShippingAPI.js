import { connect } from 'react-redux';
import { useCallback, useEffect, useState } from 'react';
import {
  VIEWS,
  COMPONENTS,
  PLATFORMS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

export const Settings = ({ settings: { shipping_info, nestedTabsStatus } }) => {
  const [view, setView] = useState(VIEWS.EDIT);

  const switchToEdit = useCallback(() => setView(VIEWS.EDIT), []);

  useEffect(() => {
    if (shipping_info && nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setView(VIEWS.READ);
    } else {
      setView(VIEWS.EDIT);
    }
  }, [shipping_info, nestedTabsStatus]);

  if (view === VIEWS.EDIT) {
    return COMPONENTS[PLATFORMS.VALUES.NATIVE].formComponent();
  }
  return COMPONENTS[PLATFORMS.VALUES.NATIVE].cardComponent(switchToEdit);
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(Settings);

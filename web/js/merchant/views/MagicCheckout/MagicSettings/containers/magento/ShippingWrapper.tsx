import { connect } from 'react-redux';
import { useEffect, useState } from 'react';
import {
  VIEWS,
  COMPONENTS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

export const ShippingWrapper = ({ settings: { platform, cod_slabs, nestedTabsStatus } }) => {
  const [view, setView] = useState<string>(VIEWS.EDIT);

  useEffect(() => {
    if (cod_slabs?.rule_type && nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setView(VIEWS.READ);
    }
  }, [platform, cod_slabs, nestedTabsStatus]);

  const switchToEdit = () => setView(VIEWS.EDIT);

  if (view === VIEWS.EDIT) {
    return COMPONENTS[platform].formComponent();
  }
  return COMPONENTS[platform].cardComponent(switchToEdit);
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(ShippingWrapper);

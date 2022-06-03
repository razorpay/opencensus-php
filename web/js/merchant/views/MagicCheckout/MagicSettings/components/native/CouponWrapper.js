import { connect } from 'react-redux';
import { useCallback, useEffect, useState } from 'react';
import { VIEWS, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import CouponCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CouponsCard';
import CouponForm from 'merchant/views/MagicCheckout/MagicSettings/containers/native/CouponsForm';

export const Settings = ({ settings: { list_promotions, apply_promotion, nestedTabsStatus } }) => {
  const [view, setView] = useState(VIEWS.EDIT);

  const switchToEdit = useCallback(() => setView(VIEWS.EDIT), []);

  useEffect(() => {
    if (list_promotions && apply_promotion && nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setView(VIEWS.READ);
    } else {
      setView(VIEWS.EDIT);
    }
  }, [list_promotions, apply_promotion, nestedTabsStatus]);

  if (view === VIEWS.EDIT) {
    return <CouponForm />;
  }
  return <CouponCard editable={true} switchToEdit={switchToEdit} />;
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(Settings);

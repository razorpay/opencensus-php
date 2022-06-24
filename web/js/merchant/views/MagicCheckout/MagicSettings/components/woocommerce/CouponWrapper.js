import { connect } from 'react-redux';
import { useEffect, useState } from 'react';
import { VIEWS, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import CouponCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CouponCard';
import CouponForm from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/CouponForm';

export const CouponWrapper = ({
  settings: { list_promotions, apply_promotion, nestedTabsStatus },
}) => {
  const [view, setView] = useState(VIEWS.EDIT);

  const switchToEdit = () => setView(VIEWS.EDIT);

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

export default connect(mapStateToProps, null)(CouponWrapper);

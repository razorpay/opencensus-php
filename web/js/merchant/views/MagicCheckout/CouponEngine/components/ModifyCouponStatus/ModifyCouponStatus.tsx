import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

// ui imports
import { ShopifySyncModalWrapper } from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponTabStyles';
import {
  CancelButton,
  ActionButton,
} from 'merchant/views/MagicCheckout/CouponEngine/components/ModifyCouponStatus/ModifyCouponStatusStyles';

// constants and helpers imports
import {
  modalTitleMap,
  successToastMessageMap,
  failureToastMessageMap,
  descriptionMap,
  actionFunctionMap,
  ctaTitleMap,
} from 'merchant/views/MagicCheckout/CouponEngine/components/ModifyCouponStatus/constants';

// helpers imports
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import { getAppType } from 'merchant/views/MagicCheckout/utils/getAppType';

const ModifyCouponStatus = ({
  closeModal,
  coupon,
  showNotification,
  status,
  updateCouponsCb,
  dashboardView,
}) => {
  const [isLoading, setLoading] = useState(false);

  const handleSubmit = async () => {
    setLoading(true);
    if (status === 'publish') {
      if (moment(coupon.active).isBefore(moment()) || moment(coupon.expiry).isBefore(moment())) {
        showNotification({
          type: 'info',
          message:
            'Coupon cannot be published as the start date or expiry date has passed. Please update the dates and try again.',
        });
        setLoading(false);
        closeModal();
        return;
      }
    }
    try {
      const { data } = await actionFunctionMap[status]({
        ...coupon,
        app_type: getAppType(dashboardView),
      });
      showNotification({
        type: 'success',
        message: successToastMessageMap[status],
      });
      updateCouponsCb(coupon, data.status);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: failureToastMessageMap[status],
      });
    } finally {
      setLoading(false);
      closeModal();
    }
  };

  return (
    <ShopifySyncModalWrapper>
      <div className="modal-header">
        <h3 className="modal-title">{modalTitleMap[status]}</h3>
        <div className="font-12">{descriptionMap[status]}</div>
      </div>
      <div className="modal-footer" style={{ borderTop: 'none', textAlign: 'left' }}>
        <CancelButton type="button" onClick={closeModal}>
          Cancel
        </CancelButton>
        <ActionButton type="button" onClick={handleSubmit} pending={isLoading}>
          {ctaTitleMap[status]}
        </ActionButton>
      </div>
    </ShopifySyncModalWrapper>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

const mapStateToProps = (state: any) => ({
  dashboardView: state.magicCheckout.dashboard_view,
});

export default connect(mapStateToProps, mapDispatchToProps)(ModifyCouponStatus);

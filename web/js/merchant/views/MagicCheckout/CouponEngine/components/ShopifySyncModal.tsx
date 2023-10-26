import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

// ui imports
import { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { ShopifySyncModalWrapper } from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponTabStyles';

// helpers
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

// api imports
import { syncShopifyCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

const ShopifySyncModal = ({ closeModal, merchantId, showNotification }) => {
  const [apiPayload, setApiPayload] = useState({
    merchant_id: merchantId,
    start_date: '',
    end_date: '',
  });
  const [isLoading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      await syncShopifyCoupons(apiPayload);
      showNotification({
        type: 'success',
        message: 'Coupons synced successfully',
      });
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: "Couldn't sync coupons from Shopify",
      });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (date, dateType) => {
    setApiPayload({
      ...apiPayload,
      [dateType]: date,
    });
  };

  return (
    <ShopifySyncModalWrapper>
      <div className="modal-header">
        <h3 className="modal-title">Sync coupons from Shopify </h3>
        <span className="font-12">Coupons will be synced only for the dates chosen</span>
      </div>
      <div className="modal-body">
        <div className="display-flex flex--column gap--14">
          <div className="date">
            <label>Start date</label>
            <Input.ToCalendar
              data-testid="shopify-sync-start-date"
              autoRender
              data-name="date"
              placeholder="DD/MM/YYYY"
              size="half_small"
              addonAfter={<i className="i i-date-range" />}
              placement="topLeft"
              allowToday={true}
              disableFutureDates={true}
              required
              onChange={(date) => {
                handleChange(moment(date).toISOString(), 'start_date');
              }}
            />
          </div>
          <div className="date">
            <label>End date</label>
            <Input.ToCalendar
              data-testid="shopify-sync-end-date"
              autoRender
              data-name="date"
              placeholder="DD/MM/YYYY"
              size="half_small"
              addonAfter={<i className="i i-date-range" />}
              placement="topLeft"
              allowToday={true}
              disableFutureDates={true}
              required
              onChange={(date) => {
                handleChange(moment(date).toISOString(), 'end_date');
              }}
            />
          </div>
        </div>
      </div>
      <div className="modal-footer" style={{ borderTop: 'none', textAlign: 'left' }}>
        <AsyncBtn type="button" onClick={closeModal}>
          Cancel
        </AsyncBtn>
        <AsyncBtn.Primary
          type="button"
          style={{ width: '176px', marginRight: 0 }}
          onClick={handleSubmit}
          disabled={!apiPayload.start_date || !apiPayload.end_date || isLoading}
          pending={isLoading}
        >
          Start Sync{' '}
        </AsyncBtn.Primary>
      </div>
    </ShopifySyncModalWrapper>
  );
};

const mapStateToProps = (state) => ({
  merchantId: state.config?.config?.id || '',
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ShopifySyncModal);

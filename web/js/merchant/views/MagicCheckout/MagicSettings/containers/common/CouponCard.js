import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsCard';
import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

const CouponCard = ({
  settings: { list_promotions, apply_promotion, one_cc_auto_fetch_coupons, platform },
  switchToEdit,
  isWooCommerce,
  edit = null,
}) => {
  const autoFetchCouponStatus = (status) => (status ? 'Enabled' : 'Disabled');

  return (
    <div className="wooc-settings-card coupon-card">
      <div className="wooc-settings-card-widget bg-white">
        <div className="display-flex wooc-settings-card-widget-wrapper">
          <div className="display-flex wooc-settings-card-widget-content">
            {isWooCommerce && (
              <div className="display-flex card-title">
                <p className="title-text">Coupons Settings</p>
                <div className="card-edit pointer" onClick={switchToEdit}>
                  <i className="i i-edit_board native-settings-edit-icon" /> Edit
                </div>
              </div>
            )}
            {platform !== PLATFORMS.MAGENTO && (
              <>
                <div className="flex setting-card-item gap--12">
                  <SettingsCard.Item label="URL for get promotions" value={list_promotions} />
                </div>
                <div className="flex setting-card-item gap--12">
                  <SettingsCard.Item label="URL for apply promotions" value={apply_promotion} />
                </div>
              </>
            )}
            <div className="flex setting-card-item gap--12 setting-card-item-last">
              <SettingsCard.Item
                label="Auto fetch coupon"
                value={autoFetchCouponStatus(one_cc_auto_fetch_coupons)}
              />
            </div>
          </div>
          {edit}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(CouponCard);

import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsCard';

const CouponCard = ({
  settings: { list_promotions, apply_promotion, one_cc_auto_fetch_coupons },
  switchToEdit,
}) => {
  const autoFetchCouponStatus = (status) => (status ? 'Enabled' : 'Disabled');

  return (
    <div className="wooc-settings-card">
      <div className="wooc-settings-card-widget bg-white">
        <div className="display-flex wooc-settings-card-widget-wrapper">
          <div className="display-flex wooc-settings-card-widget-content">
            <div className="flex setting-card-item gap--12">
              <SettingsCard.Item label="URL for get promotions" value={list_promotions} />
            </div>
            <div className="flex setting-card-item gap--12">
              <SettingsCard.Item label="URL for apply promotions" value={apply_promotion} />
            </div>
            <div className="flex setting-card-item gap--12 setting-card-item-last">
              <SettingsCard.Item
                label="Auto fetch coupon"
                value={autoFetchCouponStatus(one_cc_auto_fetch_coupons)}
              />
            </div>
          </div>
          <div className="native-card-edit p--16 pointer" onClick={switchToEdit}>
            <i className="i i-edit_board native-settings-edit-icon" /> Edit
          </div>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(CouponCard);

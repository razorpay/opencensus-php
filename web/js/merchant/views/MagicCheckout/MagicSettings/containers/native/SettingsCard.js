import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsCard';

const NativeSettingsCard = ({
  settings: { shipping_info, one_cc_international_shipping, one_cc_capture_billing_address },
  onEdit,
}) => {
  const getSettingValue = (value) => (value ? 'Enabled' : 'Disabled');
  return (
    <div className="wooc-settings-card native-shipping-card bg-white">
      <div className="wooc-settings-card-widget bg-white">
        <div className="flex flex--column gap--12 p--14 native-shipping-card-content">
          <SettingsCard.Item label="API For Shipping Info" value={shipping_info} />
          <SettingsCard.Item
            label="International Shipping"
            value={getSettingValue(one_cc_international_shipping)}
          />
          <SettingsCard.Item
            label="Capture Billing Address"
            value={getSettingValue(one_cc_capture_billing_address)}
          />
          <div className="native-card-edit pointer" onClick={onEdit}>
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

export default connect(mapStateToProps, null)(NativeSettingsCard);

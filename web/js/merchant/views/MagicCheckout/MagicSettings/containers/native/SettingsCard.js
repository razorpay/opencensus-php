import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsCard';

const NativeSettingsCard = ({ settings, onEdit }) => (
  <div className="wooc-settings-card native-shipping-card bg-white">
    <div className="wooc-settings-card-widget bg-white">
      <div className="flex gap--12 p--14 native-shipping-card-content">
        <SettingsCard.Item label="API For Shipping Info" value={settings.shipping_info} />
        <div className="native-card-edit pointer" onClick={onEdit}>
          <i className="i i-edit_board native-settings-edit-icon" /> Edit
        </div>
      </div>
    </div>
  </div>
);

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(NativeSettingsCard);

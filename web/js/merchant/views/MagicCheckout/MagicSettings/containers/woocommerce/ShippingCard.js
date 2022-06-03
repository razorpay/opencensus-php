import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsCard';
import FeeDetails from 'merchant/views/MagicCheckout/common/components/FeeDetails';

const WoocSettingsCard = ({ settings: { shipping_info, cod_slabs }, onEdit }) => (
  <div className="platform-settings-card-wrapper">
    <div className="platform-settings-card bg-white">
      <div className="platform-settings-card-info flex gap--12 p--14">
        <SettingsCard.Item label="API For Shipping Info" value={shipping_info} />
        <div className="platform-settings-edit pointer" onClick={onEdit}>
          <i className="i i-edit_board platform-settings-edit-icon" />
          Edit
        </div>
      </div>
      {cod_slabs?.rule_type ? (
        <div className="p--14 settings-fee-details">
          <FeeDetails {...cod_slabs} type="cod" label="COD" />
        </div>
      ) : null}
    </div>
  </div>
);

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(WoocSettingsCard);

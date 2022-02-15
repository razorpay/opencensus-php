import { connect } from 'react-redux';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/SettingsCard';

const NativeSettingsCard = ({ settings, ...rest }) => (
  <SettingsCard platform={PLATFORMS.VALUES.NATIVE} settings={settings} {...rest}>
    <div className="flex flex--column gap--12 p--14">
      <SettingsCard.Item label="API For Display Promotions" value={settings.list_promotions} />
      <SettingsCard.Item label="API For Apply Promotions" value={settings.apply_promotion} />
      <SettingsCard.Item label="API For Shipping Info" value={settings.shipping_info} />
    </div>
  </SettingsCard>
);

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(NativeSettingsCard);

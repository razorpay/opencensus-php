import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/SettingsCard';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const ShopifySettingsCard = ({ settings, ...rest }) => (
  <SettingsCard settings={settings} platform={PLATFORMS.VALUES.SHOPIFY} {...rest}>
    <div className="flex flex--column gap--12 border-bottom-light p--14">
      <div className="flex flex--column gap--12">
        <div className="setting-label">Shop ID</div>
        <div className="setting-value">{settings.shop_id}</div>
      </div>
    </div>
  </SettingsCard>
);

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(ShopifySettingsCard);

import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/SettingsCard';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const ShopifySettingsCard = ({ settings, ...rest }) => (
  <SettingsCard settings={settings} platform={PLATFORMS.VALUES.SHOPIFY} {...rest}>
    <div className="flex flex--column gap--12 border-bottom-light p--14">
      <div className="flex flex--column gap--12">
        <SettingsCard.Item label="Shop_ID" value={settings.shop_id} />
        <SettingsCard.Item
          label="COD Intelligence"
          value={settings.cod_intelligence ? 'Enabled' : 'Disabled'}
        />
      </div>
    </div>
  </SettingsCard>
);

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(ShopifySettingsCard);

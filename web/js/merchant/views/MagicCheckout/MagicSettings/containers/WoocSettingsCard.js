import { connect } from 'react-redux';
import SettingsCard from 'merchant/views/MagicCheckout/MagicSettings/components/SettingsCard';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import FeeDetails from 'merchant/views/MagicCheckout/common/components/FeeDetails';

const WoocSettingsCard = ({ settings, ...rest }) => {
  const origin = settings.list_promotions && new URL(settings.list_promotions).origin;

  return (
    <SettingsCard settings={settings} platform={PLATFORMS.VALUES.WOOCOMMERCE} {...rest}>
      <>
        <div className="border-bottom-light p--14">
          <SettingsCard.Item label="Domain hyperlink" value={origin} />
        </div>
        <div className="flex flex--column gap--12 border-bottom-light p--14">
          <SettingsCard.Item label="API For Display Promotions" value={settings.list_promotions} />
          <SettingsCard.Item label="API For Apply Promotions" value={settings.apply_promotion} />
          <SettingsCard.Item label="API For Shipping Info" value={settings.shipping_info} />
        </div>
        <div className="p--14">
          <FeeDetails {...settings.cod_slabs} type="cod" label="COD" />
        </div>
      </>
    </SettingsCard>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(WoocSettingsCard);

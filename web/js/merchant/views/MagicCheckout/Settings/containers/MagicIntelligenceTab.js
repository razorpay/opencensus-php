import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback } from 'react';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import CodIntelligenceToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/CodIntelligenceToggle';
import MagicIntelligence from 'merchant/views/MagicCheckout/MagicIntelligence';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const MagicIntelligenceTab = ({ settings, updateSettings }) => {
  const [codIntelligence, setCodIntelligence] = useState(settings.cod_intelligence || false);
  const switchMode = useCallback(() => {
    setCodIntelligence((prevState) => {
      const newState = !prevState;
      const params = {
        platform: settings.platform,
        cod_intelligence: newState,
      };
      if (settings.platform === PLATFORMS.VALUES.SHOPIFY) {
        params.shop_id = settings.shop_id;
      }
      updateSettings(params, false);
      return newState;
    });
  }, [codIntelligence, settings.platform, settings.shop_id]);

  return (
    <div className="magic-intelligence">
      <div className="header-wrapper">
        <div className="font-20 font-bold heading">Magic Intelligence</div>
        <div className="font-14 subtext">
          Reduce RTO orders by using Magic Intelligence to disable COD for high risk customers.
        </div>
        <div className="magic-intelligence-toggle">
          <CodIntelligenceToggle checked={codIntelligence} switchMode={switchMode} />
        </div>
      </div>
      <div className="magic-intelligence-shiprocket">
        <MagicIntelligence />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(MagicIntelligenceTab);

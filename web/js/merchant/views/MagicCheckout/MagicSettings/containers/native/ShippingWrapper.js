import { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  NATIVE_SHIPPING_VIEWS,
  BACKEND_MAPPING_FOR_NATIVE_SHIPPING_PROVIDER,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import NativeShippingTab from 'merchant/views/MagicCheckout/MagicSettings/components/native/ShippingTab';
import NativeShippingAPI from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingAPI';
import ShipRocketSettings from 'merchant/views/MagicCheckout/ShippingServices';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';

const NativeShippingTabWrapper = ({ settings, updateSettings }) => {
  let defaultView = NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION;

  switch (BACKEND_MAPPING_FOR_NATIVE_SHIPPING_PROVIDER[settings?.shipping_source]) {
    case NATIVE_SHIPPING_VIEWS.API:
      defaultView = NATIVE_SHIPPING_VIEWS.API;
      break;
    case NATIVE_SHIPPING_VIEWS.SHIPROCKET:
      defaultView = NATIVE_SHIPPING_VIEWS.SHIPROCKET;
      break;
    default:
  }

  // this is fallback for the exisiting native merchants
  if (!settings?.shipping_source && settings.shipping_info) {
    defaultView = NATIVE_SHIPPING_VIEWS.API;
  }

  const [view, setView] = useState(defaultView);

  const onSave = (shipping_source) => {
    setView(shipping_source);

    const backendKeyForShippingSource = Object.keys(
      BACKEND_MAPPING_FOR_NATIVE_SHIPPING_PROVIDER,
    ).find((key) => BACKEND_MAPPING_FOR_NATIVE_SHIPPING_PROVIDER[key] === shipping_source);

    const payload = {
      shipping_source: backendKeyForShippingSource,
      platform: 'native',
    };
    updateSettings(payload, false);
  };

  const switchToProviderSelector = () => setView(NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION);
  let Component;
  if (view === NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION) {
    return (
      <NativeShippingTab
        onNext={(view) => {
          onSave(view);
        }}
      />
    );
  } else if (view === NATIVE_SHIPPING_VIEWS.API) {
    Component = NativeShippingAPI;
  } else {
    Component = ShipRocketSettings;
  }

  return (
    <SuspenseWithLoader type="center">
      <div className="native-shipping-wrapper">
        <div className="native-shipping-header padding-16">
          <div className="display-flex">
            <div className="native-shipping-provider">{view}</div>
            <div className="view-edit pointer" onClick={switchToProviderSelector}>
              <i className="i i-edit_board view-edit-icon" />
              Edit
            </div>
          </div>
        </div>
        <div
          className={`padding-20${view === NATIVE_SHIPPING_VIEWS.SHIPROCKET ? ' bg-white' : ''}`}
        >
          <Component
            providers={[Object.keys(SHIPPING_PARTNERS)[0]]}
            isServiceabilitySettingsEnabled
          />
        </div>
      </div>
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(NativeShippingTabWrapper);

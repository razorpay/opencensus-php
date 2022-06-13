import { useState } from 'react';
import { connect } from 'react-redux';
import { NATIVE_SHIPPING_VIEWS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import NativeShippingTab from 'merchant/views/MagicCheckout/MagicSettings/components/native/ShippingTab';
import NativeShippingAPI from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingAPI';
import ShipRocketSettings from 'merchant/views/MagicCheckout/ShippingServices';

const NativeShippingTabWrapper = ({ settings }) => {
  let defaultView = NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION;
  if (settings.shipping_info) {
    defaultView = NATIVE_SHIPPING_VIEWS.API;
  }
  const [view, setView] = useState(defaultView);

  const switchToProviderSelector = () => setView(NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION);
  let Component;
  if (view === NATIVE_SHIPPING_VIEWS.PROVIDER_SELECTION) {
    return (
      <NativeShippingTab
        onNext={(view) => {
          setView(view);
        }}
      />
    );
  } else if (view === NATIVE_SHIPPING_VIEWS.API) {
    Component = NativeShippingAPI;
  } else {
    Component = ShipRocketSettings;
  }

  return (
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
      <div className={`padding-20${view === NATIVE_SHIPPING_VIEWS.SHIPROCKET ? ' bg-white' : ''}`}>
        <Component isServiceabilitySettingsEnabled={true} />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

export default connect(mapStateToProps, null)(NativeShippingTabWrapper);

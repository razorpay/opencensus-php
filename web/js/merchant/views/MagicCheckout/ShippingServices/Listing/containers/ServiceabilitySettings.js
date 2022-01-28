import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { deleteShippingMethods } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { useCallback } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import ServiceabilitySettingsModal from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketServiceabilityModal';
import DisableModal from 'merchant/views/MagicCheckout/ShippingServices/components/DisableModal';
import ShippingMethodsListing from 'merchant/views/MagicCheckout/ShippingServices/Listing/components/ShippingMethods';
import SwitchField from 'common/ui/Forms/SwitchField';

const openShippingSettingsModal = (openModal, method, providerId) => {
  openModal({
    size: 'large',
    component: <ServiceabilitySettingsModal method={method} id={providerId} />,
  });
};

const openShippingDisconnectModal = (openModal, closeModal, handleDisconnect) => {
  openModal({
    size: 'medium',
    component: (
      <DisableModal
        closeModal={closeModal}
        modalType="serviceability"
        handleDisconnect={handleDisconnect}
      />
    ),
  });
};

const ServiceabilitySettings = ({ openModal, shippingService, closeModal, deleteMethods }) => {
  const {
    shippingProviders: { id: providerId },
    shippingMethods,
  } = shippingService;
  const id = shippingMethods?.id;
  const handleDisconnect = useCallback(() => {
    deleteMethods(id);
    closeModal();
  }, [id]);

  const onShippingSettingClick = useCallback(
    (openConnect, method) => {
      if (!openConnect) {
        openShippingDisconnectModal(openModal, closeModal, handleDisconnect);
      } else {
        openShippingSettingsModal(openModal, method, providerId);
      }
    },
    [openModal, handleDisconnect],
  );

  const handleSwitchChange = useCallback(() => {
    onShippingSettingClick(!id);
  }, [id, onShippingSettingClick]);

  return (
    <>
      <div className="serviceability-settings-cta">
        <div className="display-flex justify-space-between">
          <div className="serviceability-settings-cta-heading">
            Enable serviceability using shiprocket
          </div>
          <div className="pointer">
            <SwitchField
              name="Connect"
              onChange={handleSwitchChange}
              defaultChecked={!!id}
              checked={!!id}
              type="prime"
            />
          </div>
        </div>
      </div>
      {id ? (
        <ShippingMethodsListing
          {...shippingMethods}
          onShippingSettingClick={onShippingSettingClick}
        />
      ) : null}
    </>
  );
};

const mapStateToProps = (state) => ({
  shippingService: state.shippingService,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      deleteMethods: deleteShippingMethods,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ServiceabilitySettings);

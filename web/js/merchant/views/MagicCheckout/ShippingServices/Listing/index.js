import { useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import ServiceabilitySettings from 'merchant/views/MagicCheckout/ShippingServices/Listing/containers/ServiceabilitySettings';
import Tick from 'merchant/views/MagicCheckout/ShippingServices/assets/tick.svg';
import DisableModal from 'merchant/views/MagicCheckout/ShippingServices/components/DisableModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { deleteShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';

const openShipRocketModal = (openModal, component, modalType) => {
  openModal({
    size: 'xlarge',
    className: modalType ? `Modal--${modalType}` : '',
    component,
  });
};

const openDisconnectModal = (openModal, closeModal, deleteShippingMethods, modalType) => {
  openModal({
    size: 'medium',
    component: (
      <DisableModal
        closeModal={closeModal}
        modalType={modalType}
        handleDisconnect={deleteShippingMethods}
      />
    ),
  });
};

const Listing = ({
  openModal,
  deleteProviders,
  closeModal,
  id,
  isServiceabilitySettingsEnabled,
  label,
  component,
  containerIcon,
  modalType,
}) => {
  const handleDisconnect = useCallback(() => {
    deleteProviders(id);
    closeModal();
  }, [id]);

  const onConnectClick = useCallback(() => {
    openShipRocketModal(openModal, component, modalType);
  }, [openModal, component]);

  const onDisconnectClick = useCallback(() => {
    openDisconnectModal(openModal, closeModal, handleDisconnect, modalType);
  }, [openModal, handleDisconnect]);

  return (
    <div className="shipping-account">
      <div className="connect-container display-flex">
        <div className="display-flex font-bold">
          <img
            alt={`${modalType}-logo`}
            className={`connect-container-logo ${modalType}-logo`}
            src={containerIcon}
          />
          <div className="display-flex align-center color-black">
            {label}
            {id ? (
              <div className="display-inline font-12 connected-container">
                <img alt="tick" className="" src={Tick} />
                <span>Connected</span>
              </div>
            ) : null}
          </div>
        </div>
        {!id ? (
          <div className="connect-cta display-inline color-white pointer" onClick={onConnectClick}>
            Connect
          </div>
        ) : (
          <div className="display-inline color-red pointer" onClick={onDisconnectClick}>
            Disconnect
          </div>
        )}
      </div>
      {id && isServiceabilitySettingsEnabled && <ServiceabilitySettings />}
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      deleteProviders: deleteShippingProviders,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(Listing);

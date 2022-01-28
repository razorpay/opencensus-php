import { useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import ServiceabilitySettings from 'merchant/views/MagicCheckout/ShippingServices/Listing/containers/ServiceabilitySettings';
import ShipRocketIcon from 'merchant/views/MagicCheckout/ShippingServices/assets/shiprocket.svg';
import Tick from 'merchant/views/MagicCheckout/ShippingServices/assets/tick.svg';
import ShipRocketModal from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal/';
import DisableModal from 'merchant/views/MagicCheckout/ShippingServices/components/DisableModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { deleteShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';

const openShipRocketModal = (openModal) => {
  openModal({
    size: 'xlarge',
    component: <ShipRocketModal />,
  });
};

const openDisconnectModal = (openModal, closeModal, deleteShippingMethods) => {
  openModal({
    size: 'medium',
    component: (
      <DisableModal
        closeModal={closeModal}
        modalType="disconnect"
        handleDisconnect={deleteShippingMethods}
      />
    ),
  });
};

const Listing = ({ openModal, deleteProviders, closeModal, id }) => {
  const handleDisconnect = useCallback(() => {
    deleteProviders(id);
    closeModal();
  }, [id]);

  const onConnectClick = useCallback(() => {
    openShipRocketModal(openModal);
  }, [openModal]);

  const onDisconnectClick = useCallback(() => {
    openDisconnectModal(openModal, closeModal, handleDisconnect);
  }, [openModal, handleDisconnect]);

  return (
    <div className="shipping-account">
      <div className="connect-container display-flex">
        <div className="display-flex font-bold">
          <img alt="shiprocket-logo" className="connect-container-logo" src={ShipRocketIcon} />
          <div className="display-flex align-center color-black">
            Shiprocket
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
      {id && <ServiceabilitySettings />}
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

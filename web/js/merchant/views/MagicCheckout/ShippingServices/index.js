import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  fetchAggregators,
  fetchShippingMethods,
  toggleModalFlag,
} from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { useEffect } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import BenefitsShiprocket from 'merchant/views/MagicCheckout/ShippingServices/components/BenefitsShiprocket';
import Spinner from 'common/ui/Spinner';
import Listing from 'merchant/views/MagicCheckout/ShippingServices/Listing';

const ShippingAccount = ({
  fetchProviders,
  closeModal,
  shippingService,
  modifyFlag,
  fetchMethod,
}) => {
  const {
    shouldCloseModal,
    shippingProviders: { id },
    loading,
  } = shippingService;

  useEffect(() => {
    if (!id) {
      fetchProviders();
    } else {
      fetchMethod(id);
    }
  }, [id]);

  useEffect(() => {
    if (shouldCloseModal) {
      closeModal();
      modifyFlag(false);
    }
  }, [shouldCloseModal]);

  if (loading) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }
  return (
    <div className="row">
      <div className="col-sm-5 no-padding">
        <div className="font-heading font-bold shipping-header color-black">
          Link Shiprocket Account
        </div>
        <div className="no-padding">
          Check pincode serviceability directly via Shiprocket. Get better RTO protection with
          realtime order status updates.
        </div>
        <Listing id={id} />
      </div>
      <div className="benefit-shiprocket-container display-inline">
        <BenefitsShiprocket />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  shippingService: state.shippingService,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      fetchProviders: fetchAggregators,
      fetchMethod: fetchShippingMethods,
      modifyFlag: toggleModalFlag,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ShippingAccount);

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
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const ShippingAccount = ({
  fetchProviders,
  closeModal,
  shippingService,
  modifyFlag,
  fetchMethod,
  isServiceabilitySettingsEnabled,
  magicIntelligence,
  providers,
}) => {
  const { shouldCloseModal, shippingProviders, loading } = shippingService;

  const { shiprocket } = shippingProviders;

  useEffect(() => {
    if (Object.keys(shippingProviders).length === 0) fetchProviders();
    else if (shiprocket && shiprocket.id) fetchMethod(shiprocket.id);
  }, [shiprocket]);

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
    <SuspenseWithLoader type="center">
      <div className="content-wrapper">
        <div className="row">
          <div className="col-sm-5 no-padding">
            <div className="font-heading font-bold shipping-header color-black">
              {magicIntelligence ? 'Integrate your logistic partner' : 'Link Shiprocket Account'}
            </div>
            <div className="no-padding">
              {isServiceabilitySettingsEnabled
                ? 'Check pincode serviceability directly via Shiprocket.'
                : null}
              {magicIntelligence
                ? 'Connect your delivery partner with Razorpay for better RTO protection on COD orders'
                : 'Get better RTO protection with realtime order status updates.'}
            </div>
            {providers.map((item, idx) => (
              <Listing
                key={idx}
                id={shippingProviders[item] ? shippingProviders[item].id : null}
                label={SHIPPING_PARTNERS[item].provider_type}
                isServiceabilitySettingsEnabled={isServiceabilitySettingsEnabled}
                component={SHIPPING_PARTNERS[item].component}
                containerIcon={SHIPPING_PARTNERS[item].image}
                modalType={item}
              />
            ))}
          </div>
          <div className="benefit-shiprocket-container display-inline">
            <BenefitsShiprocket showIntelligenceHighlights={!isServiceabilitySettingsEnabled} />
          </div>
        </div>
      </div>
    </SuspenseWithLoader>
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

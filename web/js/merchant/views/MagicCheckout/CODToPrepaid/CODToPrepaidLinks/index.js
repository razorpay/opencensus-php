import { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useLocation } from 'react-router-dom';

import CODPrepaidStatusContainer from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/CODPrepaidStatusContainer';
import OrderInfoSlider from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/OrderInfoDrawer';

import { updateFilters } from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/actions';
import { getURLQueryParams } from 'common/utils/rzp-utils';

import 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/CODToPrepaidLinks.styl';

const CODToPrepaidLinks = ({ updateFilters }) => {
  const [isSliderOpen, setIsSliderOpen] = useState(false);
  const location = useLocation();
  const requiredOrderId = getURLQueryParams(window.location.search).order_id;

  useEffect(() => {
    return () =>
      updateFilters({
        id: '',
        receipt: '',
        riskTier: '',
        count: 25,
        from: '',
        to: '',
        skip: 0,
        selectedPresetFromParent: null,
        items: [],
        paymentLinkStatus: '',
      });
  }, []);

  /**
   * Adding location in deps array to trigger slider on route query param change
   */
  useEffect(() => {
    if (requiredOrderId) setIsSliderOpen(true);
  }, [requiredOrderId, location]);

  return (
    <>
      <div className="cod-to-prepaid-container">
        <CODPrepaidStatusContainer />
      </div>
      {requiredOrderId && (
        <OrderInfoSlider
          isSliderOpen={isSliderOpen}
          setIsSliderOpen={setIsSliderOpen}
          requiredOrderId={requiredOrderId}
        />
      )}
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateFilters,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CODToPrepaidLinks);

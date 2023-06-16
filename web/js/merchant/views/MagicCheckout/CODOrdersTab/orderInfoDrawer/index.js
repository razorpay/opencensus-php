import { useCallback, useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { openSlider } from 'merchant_common/reducers/slider';
import Slider from 'common/ui/Slider';
import {
  receipt,
  date,
  amount,
  rtoRisk,
} from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/components/CellItem';
import { actions } from 'merchant/views/MagicCheckout/CODOrdersTab/common/CellItems';
import { useClickOutSide } from 'common/utils/customHooks';
import {
  fetchOrderInfo as fetchInfo,
  reviewCODOrders as reviewOrders,
} from 'merchant/reducers/magicCheckout/codOrders/action';
import {
  openModal as openActionModal,
  closeModal as closeActionModal,
} from 'merchant_common/reducers/modals';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import {
  confirmReview,
  showResultNotification,
} from 'merchant/views/MagicCheckout/CODOrdersTab/utils';
import OrderDetails from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderDetails';

const OrderInfoSlider = (props) => {
  const {
    isSliderOpen,
    openSlider: sliderOpen,
    setIsSliderOpen,
    requiredOrderId,
    orderInfoData,
    fetchOrderInfo,
    reviewCODOrders,
    openModal,
    closeModal,
    showNotification,
  } = props;

  const { items, loading } = orderInfoData;
  const orderInfoSliderRef = useRef();

  useEffect(() => {
    if (isSliderOpen) {
      sliderOpen();
    }
  }, [isSliderOpen]);

  useEffect(() => {
    if (fetchOrderInfo)
      fetchOrderInfo({
        id: requiredOrderId,
      });
  }, [requiredOrderId, fetchOrderInfo]);

  const onConfirm = useCallback(
    (orderId, reviewType) => {
      const promise = reviewCODOrders({
        action: reviewType,
        id: [orderId],
      });

      showResultNotification({ promise, showNotification, closeModal, reviewType });
    },
    [reviewCODOrders, showNotification, closeModal],
  );

  const onReview = useCallback(
    (orderId, reviewType) => {
      confirmReview({
        orderId,
        reviewType,
        reviewCODOrders,
        showNotification,
        openModal,
        closeModal,
        onConfirm,
      });
    },
    [reviewCODOrders, showNotification, openModal, closeModal, onConfirm],
  );

  /* callback method when we clicked outside */
  const onOutSideClick = () => {
    window.history.go(-1);
    setIsSliderOpen(false);
  };

  //  using the out side click custom hook
  useClickOutSide([orderInfoSliderRef], onOutSideClick);

  return (
    <main>
      {isSliderOpen ? (
        <Slider overlayCustomClass="magic-order-info-slider" className="order-info-slider">
          <div className="content-wrapper content-sm txn-details" ref={orderInfoSliderRef}>
            <div className="panel panel-default SliderPanel">
              <OrderDetails
                requiredOrderId={requiredOrderId}
                items={items}
                showPaymentStatus
                showRiskReasons
                showRecommendation
                orderInfoColumns={[receipt, date, rtoRisk, amount, actions(onReview)]}
                loading={loading}
              />
            </div>
          </div>
        </Slider>
      ) : null}
    </main>
  );
};
function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      openSlider,
      fetchOrderInfo: fetchInfo,
      reviewCODOrders: reviewOrders,
      openModal: openActionModal,
      closeModal: closeActionModal,
      showNotification: displayNotification,
    },
    dispatch,
  );
}

const mapStateToProps = (state) => ({
  orderInfoData: state.magicCODOrderInfo,
});

export default connect(mapStateToProps, mapDispatchToProps)(OrderInfoSlider);

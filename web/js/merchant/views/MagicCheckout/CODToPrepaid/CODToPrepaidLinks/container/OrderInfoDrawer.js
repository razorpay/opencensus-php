import { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Slider from 'common/ui/Slider';
import OrderDetails from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderDetails';
import {
  receipt,
  date,
  amount,
  rtoRisk,
  discount,
} from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/components/CellItem';
import {
  paymentLinkAction,
  paymentLinkStatus,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/CellItem';
import { openSlider } from 'merchant_common/reducers/slider';

import {
  fetchPrepayCODOrderInfo as fetchInfo,
  reviewPrepayCODOrders as reviewOrders,
} from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/actions';
import {
  openModal as openActionModal,
  closeModal as closeActionModal,
} from 'merchant_common/reducers/modals';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import { useClickOutSide } from 'common/utils/customHooks';
import { openConfirmationModal } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/utils';

import { PL_EXPIRE_CONFIRMATION_TEXT } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/constants';

const OrderInfoSlider = (props) => {
  const {
    isSliderOpen,
    openSlider: sliderOpen,
    setIsSliderOpen,
    requiredOrderId,
    orderInfoData,
    fetchPrepayCODOrderInfo,
    reviewPrepayCODOrders,
    openModal,
    closeModal,
    showNotification,
    isManualReviewEnabled,
  } = props;

  const { items, loading } = orderInfoData;
  const orderInfoSliderRef = useRef();

  const [columns, setColumns] = useState([]);

  const onReview = (paymentLinkId = null, reviewType) => {
    const { heading, description, affirmLabel, abortLabel } = PL_EXPIRE_CONFIRMATION_TEXT;

    const modalInfo = {
      heading,
      description,
      affirmativeLabel: affirmLabel,
      abortLabel,
    };

    openConfirmationModal(
      openModal,
      modalInfo,
      paymentLinkId,
      reviewType,
      reviewPrepayCODOrders,
      showNotification,
      closeModal,
    );
  };

  useEffect(() => {
    isManualReviewEnabled
      ? setColumns([
          receipt,
          date,
          amount,
          discount,
          rtoRisk,
          paymentLinkStatus,
          paymentLinkAction(onReview),
        ])
      : setColumns([
          receipt,
          date,
          amount,
          discount,
          paymentLinkStatus,
          paymentLinkAction(onReview),
        ]);
  }, [isManualReviewEnabled]);

  useEffect(() => {
    if (isSliderOpen) {
      sliderOpen();
    }
  }, [isSliderOpen]);

  useEffect(() => {
    if (fetchPrepayCODOrderInfo)
      fetchPrepayCODOrderInfo({
        id: requiredOrderId,
      });
  }, [requiredOrderId, fetchPrepayCODOrderInfo]);

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
        <Slider overlayCustomClass="magic-prepaid-order-info-slider" className="order-info-slider">
          <div className="content-wrapper content-sm txn-details" ref={orderInfoSliderRef}>
            <div className="panel panel-default SliderPanel">
              <OrderDetails
                requiredOrderId={requiredOrderId}
                items={items}
                showRiskReasons={isManualReviewEnabled}
                orderInfoColumns={columns}
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
      fetchPrepayCODOrderInfo: fetchInfo,
      reviewPrepayCODOrders: reviewOrders,
      openModal: openActionModal,
      closeModal: closeActionModal,
      showNotification: displayNotification,
    },
    dispatch,
  );
}

const mapStateToProps = (state) => ({
  orderInfoData: state.magicPrepayCODOrderInfo,
  isManualReviewEnabled: state.magicCheckout?.cod_order_control,
});

export default connect(mapStateToProps, mapDispatchToProps)(OrderInfoSlider);

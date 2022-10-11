import { Fragment, useCallback, useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { openSlider } from 'merchant_common/reducers/slider';
import Slider from 'common/ui/Slider';
import Spinner from 'common/ui/Spinner';
import {
  receipt,
  date,
  amount,
  customerDetails,
  riskReason,
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
import DataTable from 'common/ui/Table/DataTable';
import {
  confirmReview,
  showResultNotification,
} from 'merchant/views/MagicCheckout/CODOrdersTab/utils';
import { RISK_TIER_COLOR_MAPPING } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';
import { MAGIC_INTELLIGENCE_RECOMMENDATION } from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/constants';

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
              <div className="panel-heading">Razorpay Order Id: {requiredOrderId}</div>
              <div className="SliderPanel__Body">
                <div className="panel-body">
                  {loading ? (
                    <div className="content-loader loader-wrapper">
                      <Spinner />
                    </div>
                  ) : (
                    <Fragment>
                      <div className="order-details">
                        <DataTable
                          title="order-info"
                          columns={[receipt, date, rtoRisk, amount, actions(onReview)]}
                          items={items}
                          customClass="order-info-table"
                        />
                        {items[0].rto_category &&
                          items[0].risk_tier &&
                          items[0].risk_tier !== 'low' && (
                            <div
                              className={`magic-recommendation ${
                                RISK_TIER_COLOR_MAPPING[items[0].risk_tier]
                              }`}
                            >
                              <i className="i i-info-outline" />
                              {
                                MAGIC_INTELLIGENCE_RECOMMENDATION[items[0]?.rto_category][
                                  items[0]?.risk_tier
                                ]
                              }
                            </div>
                          )}
                      </div>

                      <div className="order-data-container">
                        <div className="col-sm-7 customer-details-container">
                          {items[0].customer_details ? (
                            <DataTable
                              title="customer-details-table"
                              customClass="customer-details-table"
                              columns={[customerDetails]}
                              items={items}
                            />
                          ) : null}
                        </div>
                        <div className="col-sm-5 reasons-details-container">
                          {items[0].risk_tier &&
                          items[0].risk_tier !== 'low' &&
                          items[0].rto_reasons &&
                          items[0].rto_reasons.length > 0 ? (
                            <DataTable
                              title="risk-reason-table"
                              customClass="risk-details-table"
                              columns={[riskReason]}
                              items={items}
                            />
                          ) : null}
                        </div>
                      </div>
                    </Fragment>
                  )}
                </div>
              </div>
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

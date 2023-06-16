import Spinner from 'common/ui/Spinner';
import DataTable from 'common/ui/Table/DataTable';
import {
  customerDetails,
  riskReason,
  discount,
  expiredOn,
} from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/components/CellItem';
import { paymentLinkStatus } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/CellItem';
import { RISK_TIER_COLOR_MAPPING } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';
import { MAGIC_INTELLIGENCE_RECOMMENDATION } from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/constants';

const OrderDetails = ({
  requiredOrderId,
  loading,
  orderInfoColumns,
  items,
  showRecommendation,
  showPaymentStatus,
  showRiskReasons,
}) => {
  const customClass = !showRiskReasons ? ' intelligence-table' : '';
  const item = items[0];

  return (
    <>
      <div className="panel-heading">Razorpay Order Id: {requiredOrderId}</div>
      <div className="SliderPanel__Body">
        <div className="panel-body">
          {loading ? (
            <div className="content-loader loader-wrapper">
              <Spinner />
            </div>
          ) : (
            <>
              <div className="order-details">
                <DataTable
                  title="order-info"
                  columns={orderInfoColumns}
                  items={items}
                  customClass={`order-info-table${customClass}`}
                />
                {showRecommendation &&
                  item?.rto_category &&
                  item?.risk_tier &&
                  item?.risk_tier !== 'low' && (
                    <div
                      className={`magic-recommendation ${RISK_TIER_COLOR_MAPPING[item.risk_tier]}`}
                      data-testid="recommendation-data"
                    >
                      <i className="i i-info-outline" />
                      {MAGIC_INTELLIGENCE_RECOMMENDATION[item?.rto_category][item?.risk_tier]}
                    </div>
                  )}
              </div>

              <div className="order-data-container">
                <div className="col-sm-7 customer-details-container">
                  {showPaymentStatus && item?.magic_payment_link?.status ? (
                    <DataTable
                      title="order-info"
                      columns={
                        item?.magic_payment_link?.status !== 'failed'
                          ? [paymentLinkStatus, discount, expiredOn]
                          : [paymentLinkStatus, discount]
                      }
                      customClass="order-info-table payment-info-details"
                      items={items}
                    />
                  ) : null}
                  {item?.customer_details ? (
                    <DataTable
                      title="customer-details-table"
                      customClass="customer-details-table"
                      columns={[customerDetails]}
                      items={items}
                    />
                  ) : null}
                </div>
                <div className="col-sm-5 reasons-details-container">
                  {showRiskReasons &&
                  item?.risk_tier &&
                  item?.risk_tier !== 'low' &&
                  item?.rto_reasons &&
                  item?.rto_reasons.length > 0 ? (
                    <DataTable
                      title="risk-reason-table"
                      customClass="risk-details-table"
                      columns={[riskReason]}
                      items={items}
                    />
                  ) : null}
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </>
  );
};

export default OrderDetails;

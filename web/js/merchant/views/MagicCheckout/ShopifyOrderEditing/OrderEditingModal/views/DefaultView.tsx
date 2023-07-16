import React, { Fragment, useContext, useEffect, useState } from 'react';

// UI imports
import {
  Main,
  Title,
  Subtitle,
  Aside,
  Card,
  AddItems,
  PaymentBreakup,
  TextBold,
  PaymentBreakupSubtext,
  OrdersList,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';
import LineItem from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/LineItem';
import OrderEditSummary from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/OrderSummary';

// Util / Constant / types imports
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';
import { ShowNotificationType } from 'common/typings';
import { v4 as uuid } from 'uuid';
interface DefaultViewProps {
  handleModalClose: () => void;
  showNotification: ShowNotificationType;
}

const DefaultView: React.FC<DefaultViewProps> = ({
  handleModalClose: closeModalAction,
  showNotification,
}) => {
  const { order, setView, originalOrder } = useContext(ModalContext);
  const { ADD_ITEMS, ADD_CUSTOM_ITEMS } = ORDER_EDITING_SUBTABS;
  const [lineItemKey, setLineItemKey] = useState(uuid());
  useEffect(() => {
    setLineItemKey(uuid());
  }, [order]);

  const handleAddCustomItem = () => {
    setView(ADD_CUSTOM_ITEMS);
  };

  const handleAddItem = () => {
    setView(ADD_ITEMS);
  };

  const getTotalQuantity = (lineItems: any = []) => {
    return lineItems.reduce((acc, curr) => {
      return (acc = (curr?.edited_item_quantity || 0) + acc);
    }, 0);
  };

  return (
    <Fragment>
      <Main>
        <Card>
          <AddItems>
            <div className="section-header">
              <Title>Add Items</Title>
              <div className="add-custom-item-cta" onClick={handleAddCustomItem}>
                Add Custom items
              </div>
            </div>
            <div className="search-placeholder" onClick={handleAddItem}>
              What are you looking for
            </div>
          </AddItems>
        </Card>
        <OrdersList style={{ flexGrow: 1 }}>
          <Title>Ordered items</Title>
          <div
            key={lineItemKey}
            className={`ordered-items ${order?.line_items?.length > 1 ? 'scroll' : ''}`}
          >
            {order?.line_items?.map((item) => (
              <LineItem key={uuid()} item={item} showNotification={showNotification} />
            ))}
          </div>
        </OrdersList>
        <Card>
          <PaymentBreakup>
            <Title>Payment Details</Title>
            <ul>
              <Subtitle>
                <li>
                  <TextBold> {getTotalQuantity(order?.line_items)} items</TextBold>
                  <div>₹ {(order?.new_sub_total_price / 100).toFixed(2) || 0}</div>
                </li>
              </Subtitle>
              <Subtitle>
                <li>
                  <TextBold> Discount {order.new_cart_level_discount ? '*' : ''}</TextBold>
                  <div>₹ {(order?.new_cart_level_discount / 100).toFixed(2) || 0}</div>
                </li>
              </Subtitle>
              <Subtitle>
                <li>
                  <TextBold> Delivery Charges</TextBold>
                  <div>₹ {(order?.original_shipping_charge / 100).toFixed(2) || 0}</div>
                </li>
              </Subtitle>
              <Subtitle>
                <li>
                  <TextBold>
                    {' '}
                    Total Amount{' '}
                    <PaymentBreakupSubtext>
                      {' '}
                      (including taxes and charges)
                    </PaymentBreakupSubtext>{' '}
                  </TextBold>
                  <div>₹ {(order?.new_total_price / 100).toFixed(2)}</div>
                </li>
              </Subtitle>
              <hr />
              <Subtitle>
                <li>
                  <TextBold>Paid By Customer</TextBold>
                  <TextBold>
                    ₹{' '}
                    {Number(
                      (originalOrder?.original_net_customer_paid_amount / 100).toFixed(2) || 0,
                    )}
                  </TextBold>
                </li>
              </Subtitle>
            </ul>
          </PaymentBreakup>
        </Card>
      </Main>

      <Aside>
        <OrderEditSummary closeModalAction={closeModalAction} showNotification={showNotification} />
      </Aside>
    </Fragment>
  );
};

export default DefaultView;

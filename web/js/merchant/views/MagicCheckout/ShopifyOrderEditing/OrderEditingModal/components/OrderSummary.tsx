import React, { Fragment, useContext, useState } from 'react';

// UI imports
import {
  Title,
  Subtitle,
  Card,
  PaymentBreakup,
  TextBold,
  FormGroupWithCheckbox,
  FormCheckboxInput,
  FormLabel,
  CtaContainer,
  EditReasonWrapper,
  CommentBoxWrapper,
  CommentBox,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// Util / Constants imports
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { isEqual } from 'lodash';

// Types imports
import { ShowNotificationType } from 'common/typings';

// API imports
import { commitOrderEditing } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';
interface OrderEditSummaryProps {
  closeModalAction: () => void;
  showNotification: ShowNotificationType;
}

const OrderEditSummary: React.FC<OrderEditSummaryProps> = ({
  closeModalAction,
  showNotification,
}) => {
  const [isLoading, setLoading] = useState(false);
  const { order, originalOrder, edit_id } = useContext(ModalContext);
  const [commitChangesPayload, setCommitChangesPayload] = useState({
    send_invoice: true,
    reason_note: '',
  });

  const handleSave = async () => {
    setLoading(true);
    try {
      await commitOrderEditing(edit_id, commitChangesPayload);
      showNotification({
        type: 'success',
        message: 'Order edited successfully',
      });
      closeModalAction();
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error.errors || 'Something went wrong',
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Fragment>
      <Card style={{ flexGrow: 1 }}>
        <Title> Summary</Title>
        {isEqual(originalOrder, order) ? (
          <Subtitle>No changes have been made</Subtitle>
        ) : (
          <PaymentBreakup>
            <Subtitle>
              <li>
                <div>Updated Amount</div>
                <TextBold>₹ {(order?.new_total_price / 100).toFixed(2) || 0}</TextBold>
              </li>
            </Subtitle>
            <Subtitle>
              <li>
                <div>Paid By Customer</div>
                <TextBold>
                  ₹ {Number((order?.original_net_customer_paid_amount / 100).toFixed(2) || 0)}
                </TextBold>
              </li>
            </Subtitle>
            <hr />
            <Subtitle>
              <TextBold>
                {' '}
                <li>
                  <div>Amount to {order?.new_total_outstanding > 0 ? 'collect' : 'refund'} </div>
                  <div>₹ {Math.abs(order?.new_total_outstanding / 100).toFixed(2)}</div>
                </li>
              </TextBold>
            </Subtitle>
            <hr />
            <FormGroupWithCheckbox>
              <FormCheckboxInput
                type="checkbox"
                id="shipping-charges"
                name="requiresShipping"
                checked={commitChangesPayload.send_invoice}
                onChange={(e) => {
                  setCommitChangesPayload({
                    ...commitChangesPayload,
                    send_invoice: e.target.checked,
                  });
                }}
              />
              <FormLabel htmlFor="shipping-charges">Send notification to the customer</FormLabel>
            </FormGroupWithCheckbox>
            <hr />
            <CtaContainer>
              <button disabled={isLoading} onClick={handleSave} className="primary-cta w-100">
                Update Order
              </button>
            </CtaContainer>
          </PaymentBreakup>
        )}
      </Card>
      {!isEqual(originalOrder, order) ? (
        <EditReasonWrapper>
          <Title>Reason for edit</Title>
          <CommentBoxWrapper>
            <CommentBox
              placeholder="Please enter edit reason"
              value={commitChangesPayload.reason_note}
              onChange={(e) => {
                setCommitChangesPayload({
                  ...commitChangesPayload,
                  reason_note: e.target.value,
                });
              }}
            />
          </CommentBoxWrapper>
        </EditReasonWrapper>
      ) : null}
    </Fragment>
  );
};

export default OrderEditSummary;

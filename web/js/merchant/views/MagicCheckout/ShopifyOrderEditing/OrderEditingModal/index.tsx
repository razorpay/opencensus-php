import React, { Fragment, useContext, useEffect, useState } from 'react';
import { connect, ConnectedProps } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// UI imports
import Loader from 'common/ui/Loader';
import ModalHeader from 'common/ui/ModalHeader';
import {
  AddCustomItemView,
  AddItemView,
  DefaultView,
  DiscountView,
  ExitIntent,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/views';
import {
  OrderEditingModalContent,
  OrderEditingModalWrapper,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// Util / Constant imports
import isEmpty from 'lodash/isEmpty';
import {
  HEADER_TITLE,
  ORDER_EDITING_SUBTABS,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { closeModal } from 'merchant_common/reducers/modals';

// Types imports
import { ShowNotificationType } from 'common/typings';
import { showNotification } from 'merchant_common/reducers/notifications';

// API imports
import { beginOrderEditing } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

interface OrderEditingModalProps extends PropsFromRedux {
  id: string;
  closeModal: () => void;
  showNotification: ShowNotificationType;
  display_id: string;
}

const OrderEditingModal: React.FC<OrderEditingModalProps> = ({
  closeModal: closeModalAction,
  id,
  showNotification,
  display_id,
}) => {
  const { order, setOrder, setView, view, setEditId, setOriginalOrder } = useContext(ModalContext);
  const { DEFAULT, ADD_ITEMS, ADD_CUSTOM_ITEMS, ADD_DISCOUNT, EXIT_INTENT } = ORDER_EDITING_SUBTABS;
  const [isLoading, setLoading] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        // This API is used to begin the order editing
        const res = await beginOrderEditing(id);
        setOrder(res.data.edited_order);
        setOriginalOrder(res.data.edited_order);
        setEditId(res.data.edited_order.edit_id);
        setView(DEFAULT);
        setLoading(false);
      } catch (error: any) {
        showNotification({
          type: 'error',
          message: 'Something went wrong',
        });
        closeModalAction();
      }
    };

    fetchData();
  }, []);

  const handleModalClose = () => {
    closeModalAction();
  };

  // Function for specifying the height of the modal based on total items in the cart
  const getModalHeight = () => {
    if (!isEmpty(order)) {
      const totalItemsToDisplay = order.line_items.filter(
        (item) => item?.edited_item_quantity !== 0,
      ).length;

      if (view === EXIT_INTENT || view === ADD_DISCOUNT) return 'auto';
      if (totalItemsToDisplay > 1) return '630px';
    }
    return '';
  };

  return (
    <OrderEditingModalContent>
      <OrderEditingModalWrapper>
        {isLoading ? (
          <Loader />
        ) : (
          <Fragment>
            <ModalHeader
              title={`${
                view === EXIT_INTENT ? HEADER_TITLE.EXIT_INTENT : HEADER_TITLE.DEFAULT
              } - ${display_id}`}
              extraClass="no-padding"
              onCloseClick={view === EXIT_INTENT ? null : () => setView(EXIT_INTENT)}
            />
            <div className="modal-body-wrapper">
              <div className="modal-body" style={{ height: getModalHeight() }}>
                {view === DEFAULT && (
                  <DefaultView
                    handleModalClose={handleModalClose}
                    showNotification={showNotification}
                  />
                )}
                {view === ADD_CUSTOM_ITEMS && (
                  <AddCustomItemView showNotification={showNotification} />
                )}
                {view === ADD_ITEMS && <AddItemView showNotification={showNotification} />}
                {view === ADD_DISCOUNT && <DiscountView showNotification={showNotification} />}
                {view === EXIT_INTENT && <ExitIntent handleModalClose={handleModalClose} />}
              </div>
              {order && order.new_cart_level_discount && view === DEFAULT ? (
                <div className="callout-msg">
                  * Editing this order might result in removing cart discounts
                </div>
              ) : null}
            </div>
          </Fragment>
        )}
      </OrderEditingModalWrapper>
    </OrderEditingModalContent>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

const connector = connect(null, mapDispatchToProps);

type PropsFromRedux = ConnectedProps<typeof connector>;

export default connector(OrderEditingModal);

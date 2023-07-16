import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

// UI imports
import DataTable from 'common/ui/Table/DataTable';
import {
  shopifyOrderId,
  date,
  actions,
  orderStatus,
  razorpayId,
  customerName,
  price,
  paymentStatus,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/common/CellItems';
import OrderFilters from 'merchant/views/MagicCheckout/ShopifyOrderEditing/common/OrderFilters';
import EmptyComponent from 'merchant/views/MagicCheckout/ShopifyOrderEditing/common/EmptyComponent';
import OrderEditingModal from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal';

// Util imports
import * as ModalActions from 'merchant_common/reducers/modals';
import { ModalProvider } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';

// Types imports
import { showNotification } from 'merchant_common/reducers/notifications';
import { ShowNotificationType } from 'common/typings';

// API imports
import { fetchShopifyOrders } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

interface Item {
  id: string;
  display_id: string;
  merchantEditable: boolean;
  createdAt: string;
  name: string;
  price: number;
  displayFulfillmentStatus: string;
}

interface OrderEditingModalProps {
  openModal: (options: { size: string; className: string; component: JSX.Element }) => void;
  showNotification: ShowNotificationType;
}

const PageContent: React.FC<OrderEditingModalProps> = ({ openModal, showNotification }) => {
  const [skip, setSkip] = useState(0);
  const [count, setCount] = useState(10);
  const [searchTerm, setSearchTerm] = useState('');
  const [itemsArray, setItemsArray] = useState<Item[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setItemsArray([]);
        setIsLoading(true);
        const { data } = await fetchShopifyOrders(skip, count, searchTerm);
        setItemsArray(data.orders);
      } catch (error: any) {
        showNotification({
          type: 'error',
          message: 'Something went wrong',
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [skip, count, searchTerm, showNotification]);

  const onSubmitHandler = (value: string) => {
    setSkip(0);
    setCount(10);
    setSearchTerm(value);
  };

  const resetHandler = () => {
    setSkip(0);
    setCount(10);
    setSearchTerm('');
  };

  const paginate = (params) => {
    setSkip(params.skip);
    setCount(params.count);
  };

  const openOrderEditingModal = (id: string, display_id: string) => {
    openModal({
      size: 'large',
      className: 'order-editing-modal',
      component: (
        <ModalProvider>
          <OrderEditingModal id={id} display_id={display_id} />
        </ModalProvider>
      ),
    });
  };

  return (
    <div className="orders-container">
      <OrderFilters onSubmitHandler={onSubmitHandler} resetHandler={resetHandler} />
      <DataTable
        title="orders"
        columns={[
          razorpayId,
          shopifyOrderId,
          date,
          customerName,
          price,
          orderStatus,
          paymentStatus,
          actions(openOrderEditingModal),
        ]}
        items={itemsArray}
        customClass="magic-cod-orders-table order-editing-table"
        loading={isLoading}
        skip={skip}
        count={count}
        paginate={paginate}
        EmptyComponent={EmptyComponent}
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      showNotification,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(PageContent);

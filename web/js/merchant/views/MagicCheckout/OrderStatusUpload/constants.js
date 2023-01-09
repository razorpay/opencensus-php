import OrderStatusTab from 'merchant/views/MagicCheckout/OrderStatusTab';
import RTOHistoryUpload from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload';

export const NAV_ITEM = [
  {
    id: 'rtoDeliveryStatus',
    title: 'Delivery Statuses',
    component: <OrderStatusTab />,
  },
  {
    id: 'rtoHistoryUpload',
    title: 'Order History',
    component: <RTOHistoryUpload />,
  },
];

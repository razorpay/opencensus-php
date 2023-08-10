import OrderStatusTab from 'merchant/views/MagicCheckout/OrderStatusTab';
import RTOHistoryUpload from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload';

export const NAV_ITEM = [
  {
    id: 'rtoDeliveryStatus',
    title: 'Monthly delivery data',
    component: <OrderStatusTab />,
  },
  {
    id: 'rtoHistoryUpload',
    title: 'Pre-Magic delivery data',
    component: <RTOHistoryUpload />,
  },
];

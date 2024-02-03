import React from 'react';

export interface GCMSOrderSession {
  resellerId: string;
  orderId: string;
  setOrderId: (string) => void;
}

export const OrderSessionContext = React.createContext<GCMSOrderSession>({
  resellerId: '',
  orderId: '',
  setOrderId: () => {},
});

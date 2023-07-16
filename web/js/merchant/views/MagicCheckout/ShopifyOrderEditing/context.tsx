import React, { useState, createContext, ReactNode } from 'react';

interface ModalContextValue {
  order: any;
  setOrder: React.Dispatch<React.SetStateAction<any>>;
  view: string;
  setView: React.Dispatch<React.SetStateAction<string>>;
  edit_id: string;
  setEditId: React.Dispatch<React.SetStateAction<string>>;
  lineItemEditId: string;
  setLineItemEditId: React.Dispatch<React.SetStateAction<string>>;
  originalOrder: any;
  setOriginalOrder: React.Dispatch<React.SetStateAction<any>>;
  customDiscountID: string;
  setCustomDiscountID: React.Dispatch<React.SetStateAction<string>>;
}

interface ModalProviderProps {
  children: ReactNode;
}

const initialValue = {
  order: '',
  setOrder: () => {},
  view: '',
  setView: () => {},
  edit_id: '',
  setEditId: () => {},
  lineItemEditId: '',
  setLineItemEditId: () => {},
  originalOrder: '',
  setOriginalOrder: () => {},
  customDiscountID: '',
  setCustomDiscountID: () => {},
};

export const ModalContext = createContext<ModalContextValue>(initialValue);

export function ModalProvider({ children }: ModalProviderProps) {
  const [order, setOrder] = useState({});
  const [view, setView] = useState('default');
  const [edit_id, setEditId] = useState('');
  const [lineItemEditId, setLineItemEditId] = useState('');
  const [originalOrder, setOriginalOrder] = useState({});
  const [customDiscountID, setCustomDiscountID] = useState('');

  const contextValue: ModalContextValue = {
    order,
    setOrder,
    view,
    setView,
    edit_id,
    setEditId,
    lineItemEditId,
    setLineItemEditId,
    originalOrder,
    setOriginalOrder,
    customDiscountID,
    setCustomDiscountID,
  };

  return <ModalContext.Provider value={contextValue}>{children}</ModalContext.Provider>;
}

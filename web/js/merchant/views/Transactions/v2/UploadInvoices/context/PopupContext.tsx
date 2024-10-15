import React, { useState, createContext } from 'react';

import { PopupContextType } from 'merchant/views/Transactions/v2/UploadInvoices/types';

export const PopupContext = createContext<PopupContextType>({
  popupDetails: {
    type: null,
    props: null,
  },
  openPopup: () => {},
  closePopup: () => {},
});

const PopupProvider = ({ children }) => {
  const [popupDetails, setPopupDetails] = useState({
    type: null,
    props: null,
  });

  const openPopup = (type, props) => {
    setPopupDetails({ type, props });
  };

  const closePopup = () => {
    setPopupDetails({ type: null, props: null });
  };

  const popupValues = {
    openPopup,
    closePopup,
    popupDetails,
  };

  return <PopupContext.Provider value={popupValues}>{children}</PopupContext.Provider>;
};

export default PopupProvider;

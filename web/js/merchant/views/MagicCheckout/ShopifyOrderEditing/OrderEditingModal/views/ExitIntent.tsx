import React, { useContext } from 'react';

// UI imports
import {
  CtaContainer,
  ModalContent,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// util imports
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';

interface ExitIntentProps {
  handleModalClose: () => void;
}

const ExitIntent: React.FC<ExitIntentProps> = ({ handleModalClose }) => {
  const { setView } = useContext(ModalContext);

  const handleNegativeClick = () => {
    setView(ORDER_EDITING_SUBTABS.DEFAULT);
  };

  const handlePositiveClick = () => {
    handleModalClose();
  };

  return (
    <ModalContent>
      <div>
        Once you cancel the edit, all the unsaved changes will be deleted. Are you sure you want to
        exit the order edit?
      </div>
      <hr />
      <CtaContainer>
        <button onClick={handleNegativeClick} className="secondary-cta">
          Cancel
        </button>
        <button onClick={handlePositiveClick} className="primary-cta">
          Exit Edit Order
        </button>
      </CtaContainer>
    </ModalContent>
  );
};

export default ExitIntent;

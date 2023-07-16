import React, { useState } from 'react';

// UI components
import {
  QuantityWrapper,
  CircleButton,
  CircleButtonIcon,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// assets
import plus from 'assets/plus.svg';
import minus from 'assets/minus.svg';

interface QuantityCounterProps {
  initialState?: number;
  maxQuantity?: number | undefined;
  onQuantityChange?: (quantity: number) => Promise<boolean> | boolean;
}

const QuantityCounter: React.FC<QuantityCounterProps> = ({
  initialState = 1,
  maxQuantity = Number.POSITIVE_INFINITY,
  onQuantityChange = () => {},
}) => {
  const [quantity, setQuantity] = useState<number>(initialState);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const handleQuantityChange = async (updatedQuantity: number) => {
    setIsLoading(true);
    const newQuantity = Math.max(0, Math.min(updatedQuantity, maxQuantity));
    const isSuccess = await onQuantityChange(newQuantity);

    // handle the case in case api call is not successfull then dont update the quantity
    if (isSuccess) {
      setQuantity(newQuantity);
    }
    setIsLoading(false);
  };

  return (
    <QuantityWrapper>
      <CircleButton
        disabled={quantity <= 0 || isLoading}
        onClick={() => handleQuantityChange(quantity - 1)}
      >
        <CircleButtonIcon src={minus} alt="minus" />
      </CircleButton>

      {quantity}
      <CircleButton
        disabled={quantity >= maxQuantity || isLoading}
        onClick={() => handleQuantityChange(quantity + 1)}
      >
        <CircleButtonIcon src={plus} alt="plus" />
      </CircleButton>
    </QuantityWrapper>
  );
};

export default QuantityCounter;

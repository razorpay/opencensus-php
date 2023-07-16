import React from 'react';

// UI imports
import QuantityCounter from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/QuantityCounter';
import {
  AvailableItem,
  ItemImage,
  ItemTotalQuantity,
  ItemPrice,
  ItemQuantity,
  SearchItemWrapper,
  SearchItemContainer,
  SmallText,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

interface SearchItemProps {
  data: {
    image_url: string;
    name: string;
    variant_price: number;
    variant_id: string;
    variant_title: string;
    variant_inventory: number;
  };
  onSelectItem: (payload: any) => void;
}

const SearchItem: React.FC<SearchItemProps> = ({ data, onSelectItem }) => {
  const {
    image_url = '',
    name = '',
    variant_price = 0,
    variant_id = '',
    variant_title = '',
    variant_inventory = 0,
  } = data;

  const handleSelect = (quantity: number) => {
    const payload = {
      quantity,
      variant_id,
    };

    onSelectItem(payload);
    return true;
  };

  return (
    <SearchItemContainer>
      <SearchItemWrapper>
        <AvailableItem>
          <ItemImage src={image_url} alt={name} />
          <div>
            <div>{name}</div>
            <SmallText>{variant_title}</SmallText>
          </div>
        </AvailableItem>
        <ItemTotalQuantity> {variant_inventory} available </ItemTotalQuantity>
        <ItemPrice>₹ {(variant_price / 100).toFixed(2)} </ItemPrice>
        <ItemQuantity>
          <QuantityCounter
            initialState={0}
            maxQuantity={variant_inventory}
            onQuantityChange={handleSelect}
          />
        </ItemQuantity>
      </SearchItemWrapper>
      <hr />
    </SearchItemContainer>
  );
};

export default SearchItem;

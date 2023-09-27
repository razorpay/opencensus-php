import React from 'react';

import { Product } from './types';
import { ImageContainer as ProductImage } from './styled';
import {
  CheckboxWrapper,
  ModalItem,
} from 'merchant/views/MagicCheckout/common/components/SettingsModal/styles';

type ProductItemProps = {
  item: Product & {
    selected: boolean;
  };
  handleSelectedProduct: (item: Product, status: boolean) => void;
  isDisabled: boolean;
};

const ProductItem = ({
  item,
  handleSelectedProduct,
  isDisabled,
}: ProductItemProps): JSX.Element => {
  const handleChange = (e) => {
    handleSelectedProduct(item, e.target.checked);
  };

  return (
    <ModalItem isDisabled={isDisabled} data-testid="product-item">
      <CheckboxWrapper>
        <input
          id={item.id}
          type="checkbox"
          defaultChecked={item.selected}
          className="modal-checkbox"
          onChange={handleChange}
        />

        <ProductImage>
          <img src={item.image_url} alt={item.name} loading="lazy" />
        </ProductImage>
        <label htmlFor={item.id}>{item.name}</label>
      </CheckboxWrapper>
      {isDisabled ? <p className="in-entity-text">In {item.internal_category} category</p> : null}
    </ModalItem>
  );
};

export default ProductItem;

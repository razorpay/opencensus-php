import React, { useRef } from 'react';

import { Product } from './types';
import { ProductImage } from './styled';
import { SELECT_ALL_PRODUCTS } from './constants';

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
  const inputRef = useRef<HTMLInputElement>(null);

  const handleChange = (e) => {
    handleSelectedProduct(item, e.target.checked);
  };

  return (
    <div className={`${isDisabled ? 'disabled' : ''} modal-item`}>
      <div className="checkbox-wrapper">
        {/* TODO: move to blade */}
        <input
          id={item.id}
          type="checkbox"
          ref={inputRef}
          checked={item.selected}
          className="modal-checkbox"
          onChange={handleChange}
        />

        {item.id !== SELECT_ALL_PRODUCTS.id ? (
          <ProductImage>
            <img src={item.image_url} alt={item.name} loading="lazy" />
          </ProductImage>
        ) : null}
        <label htmlFor={item.id}>{item.name}</label>
      </div>
      {isDisabled ? <p className="in-zone-text">In {item.internal_category} category</p> : null}
    </div>
  );
};

export default ProductItem;

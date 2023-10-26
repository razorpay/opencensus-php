import React, { useState, useEffect } from 'react';

import Input from 'common/new-ui/Input';
import {
  ItemWrapper,
  ItemContainer,
  Image,
  ProductName,
  VariantContainer,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/AddProductModal';

// types imports
import { SearchItemProps } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/SearchItem/types';

const SearchItem: React.FC<SearchItemProps> = ({
  product,
  selectedProducts,
  setSelectedProducts,
}) => {
  const { id, image_url, name, variants } = product;
  const [shouldShowVariantDetails, setShouldShowVariantDetails] = useState(false);
  const [isSelectAllChecked, setSelectAllChecked] = useState(false);

  useEffect(() => {
    if (selectedProducts[id]?.variants.length === variants.length) {
      setSelectAllChecked(true);
    }

    if (selectedProducts[id]?.variants.length === 0) {
      setSelectAllChecked(false);
      setSelectedProducts((prev) => {
        const newSelectedProducts = { ...prev };
        delete newSelectedProducts[id];
        return newSelectedProducts;
      });
    }
  }, [id, selectedProducts, setSelectedProducts, variants.length]);

  const handleVariantCheckboxChange = (variantId: number) => {
    setSelectedProducts((prev) => {
      const newSelectedProducts = { ...prev };
      if (newSelectedProducts[id]) {
        if (newSelectedProducts[id].variants.includes(variantId)) {
          newSelectedProducts[id].variants = newSelectedProducts[id].variants.filter(
            (id) => id !== variantId,
          );
        } else {
          newSelectedProducts[id].variants.push(variantId);
        }
      } else {
        newSelectedProducts[id] = {
          product_id: id,
          product_name: name,
          product_image_url: image_url,
          variants: [variantId],
        };
      }
      return newSelectedProducts;
    });
  };

  const handleSelectAllToggle = () => {
    setSelectedProducts((prev) => {
      const newSelectedProducts = { ...prev };
      if (!isSelectAllChecked) {
        newSelectedProducts[id] = {
          product_id: id,
          product_name: name,
          product_image_url: image_url,
          variants: variants.map((variant) => variant.id),
        };
      } else {
        delete newSelectedProducts[id];
      }
      return newSelectedProducts;
    });

    setSelectAllChecked(!isSelectAllChecked);
  };

  return (
    <ItemWrapper>
      <ItemContainer>
        <div className="display-flex gap--12">
          <Input.Check autoRender checked={isSelectAllChecked} onChange={handleSelectAllToggle} />
          <div
            onClick={() => setShouldShowVariantDetails(!shouldShowVariantDetails)}
            className="display-flex gap--12 align-center"
          >
            <Image src={image_url} alt={name} />
            <ProductName>{name}</ProductName>
          </div>
        </div>
        <div>{variants.length} variant</div>
      </ItemContainer>
      <hr />
      {shouldShowVariantDetails && (
        <VariantContainer>
          {product.variants.map((variant) => (
            <div key={variant.id}>
              <div className="display-flex gap--12">
                <Input.Check
                  autoRender
                  checked={selectedProducts[id]?.variants.includes(variant.id)}
                  onChange={() => handleVariantCheckboxChange(variant.id)}
                />
                <div className="display-flex gap--12 align-center">
                  <Image src={image_url} alt={name} />
                  <ProductName>
                    {variant.title}
                    {variant.sku ? ` | ${variant.sku}` : ''}
                  </ProductName>
                </div>
              </div>
              <hr />
            </div>
          ))}
        </VariantContainer>
      )}
    </ItemWrapper>
  );
};

export default SearchItem;

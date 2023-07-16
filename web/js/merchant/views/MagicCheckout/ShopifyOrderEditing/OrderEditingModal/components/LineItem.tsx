import React, { Fragment, useContext, useState } from 'react';

// UI imports
import QuantityCounter from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/QuantityCounter';
import {
  LineItemWrapper,
  ProductPrice,
  Product,
  ProductImage,
  PlaceholderImage,
  ProductDetails,
  ProductName,
  ProductDetail,
  ProductQuantity,
  CircleButton,
  CircleButtonIcon,
  OfferOutline,
  StrikedPrice,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// Util / Constant
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';

// Types imports
import { ShowNotificationType } from 'common/typings';

// Assets imports
import trashOutline from 'assets/trash-outline.svg';

// API imports
import {
  removeLineItem,
  editLineItemQuantity,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

type LineItem = {
  [x: string]: any;
  name: string;
  image_url: string;
  sku: string;
  item_quantity: number;
  inventory_quantity: number;
  price: number;
  discounted_price: number;
  edit_id: string;
  variant: any;
  original_price: number;
  discount_applied: string;
  edited_item_quantity: number;
};

interface LineItemProps {
  item: LineItem;
  showNotification: ShowNotificationType;
}

const LineItem: React.FC<LineItemProps> = ({ item, showNotification }) => {
  const {
    name = '',
    image_url = '',
    discounted_price = 0,
    original_price = 0,
    sku = '',
    inventory_quantity = Number.POSITIVE_INFINITY,
    item_quantity = 0,
    edit_id: lineItemEditId = '',
    discount_applied = '',
    edited_item_quantity = 0,
    variant = {},
  } = item;

  const { setOrder, setView, edit_id, setLineItemEditId, setCustomDiscountID, order } =
    useContext(ModalContext);
  const [isLoading, setIsLoading] = useState(false);

  const handleDelete = async (id: string) => {
    setIsLoading(true);
    try {
      const response = await removeLineItem(edit_id, id);
      setOrder(response.data.edited_order);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error.errors || 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleSetViewDiscount = () => {
    setLineItemEditId(lineItemEditId);
    setCustomDiscountID(discount_applied || '');
    setView(ORDER_EDITING_SUBTABS.ADD_DISCOUNT);
  };

  const handleQuantityChange = async (quantity: number) => {
    try {
      const response = await editLineItemQuantity(edit_id, {
        quantity,
        edit_line_item_id: lineItemEditId as string,
        order_cart_discount: order.new_cart_level_discount,
        is_cart_item_discount_applied: Boolean(
          discount_applied && original_price === discounted_price,
        ),
      });
      setOrder(response.data.edited_order);
      return true;
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error.errors || 'Something went wrong',
      });
      return false;
    }
  };

  return (
    <Fragment>
      {edited_item_quantity ? (
        <LineItemWrapper>
          <Product>
            {image_url ? (
              <ProductImage src={image_url} alt={name} />
            ) : (
              <PlaceholderImage> {name[0].toUpperCase()}</PlaceholderImage>
            )}

            <ProductDetails>
              <ProductName>{name}</ProductName>
              {variant.name && (
                <ProductDetail> {variant.name.replace(`${name} - `, '')}</ProductDetail>
              )}
              {sku && <ProductDetail>{`SKU: ${sku}`}</ProductDetail>}
              <ProductQuantity>
                Quantity{' '}
                <QuantityCounter
                  initialState={item_quantity}
                  maxQuantity={variant.id ? inventory_quantity : Number.POSITIVE_INFINITY}
                  onQuantityChange={handleQuantityChange}
                />
                <CircleButton onClick={() => handleDelete(lineItemEditId)} disabled={isLoading}>
                  <CircleButtonIcon src={trashOutline} alt="trash-outline" />
                </CircleButton>
                <CircleButton onClick={handleSetViewDiscount} disabled={!Boolean(item_quantity)}>
                  <OfferOutline className="i i-offer2" />
                </CircleButton>
              </ProductQuantity>
            </ProductDetails>
          </Product>
          <div>
            {discounted_price === original_price ? (
              <ProductPrice>₹ {original_price / 100}</ProductPrice>
            ) : (
              <Fragment>
                <ProductPrice>₹ {discounted_price / 100}</ProductPrice>
                <StrikedPrice>₹ {original_price / 100}</StrikedPrice>
              </Fragment>
            )}
          </div>
        </LineItemWrapper>
      ) : null}
    </Fragment>
  );
};

export default LineItem;

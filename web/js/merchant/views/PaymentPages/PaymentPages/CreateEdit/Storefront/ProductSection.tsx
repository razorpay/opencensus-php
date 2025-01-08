import {
  EditComposeIcon,
  EditIcon,
  MoreHorizontalIcon,
  Text,
  TrashIcon,
} from '@razorpay/blade/components';
import React, { useEffect, useRef, useState } from 'react';
import {
  OptionsDropdownWrapper,
  BottomSheetItem,
  BottomSheetWrapper,
  ProductItemsWrapper,
  ProductsWrapper,
} from './styled';
import { IconWrapper } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/styled';
import { IProductItem, IProductItems, IProductSection, TDropdown } from './types';
import { formatTextAmountField } from 'merchant/views/PaymentPages/common/Products/utils';
import { getPrimaryImage } from './utils';
import BottomSheet from 'common/components/BottomSheet';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const ProductItem = ({
  product,
  currentDropdown,
  setCurrentDropdown,
  editProduct,
  removeProduct,
  isMobile,
}: IProductItem): React.ReactElement => {
  const { images, product_name, discounted_amount, amount, id } = product;
  const isDropdownOpen = currentDropdown === id;

  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    // The bottomsheet used on mobile has its own implementation for handling clicking outside (for onClose). Therefore we are using that itself.
    if (isDropdownOpen && !isMobile) {
      document.addEventListener('pointerdown', listener);
    } else {
      document.removeEventListener('pointerdown', listener);
    }
    return () => {
      document.removeEventListener('pointerdown', listener);
    };
  }, [isDropdownOpen, isMobile]);

  function listener(event: MouseEvent | TouchEvent) {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    if (!ref.current || ref.current.contains(event.target)) {
      // execute the configured onClick behavior, if clicked inside
      return;
    }
    // if clicked outside the dropdown, then close the dropdown
    closeDropdown();
  }

  function handleEdit(id: string) {
    if (editProduct) {
      editProduct(id);
      setTimeout(() => closeDropdown(), 500);
      analyticsTrack({
        objectName: 'Edit option for a product',
        actionName: 'Clicked',
        screen: 'Create storefront page',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
          productId: id,
          product_page: 'Storefront Page',
        },
      });
    }
  }

  function handleRemove(id: string) {
    if (removeProduct) {
      removeProduct(id);
      setTimeout(() => closeDropdown(), 500);
      analyticsTrack({
        objectName: 'Remove option for a product',
        actionName: 'Clicked',
        screen: 'Create storefront page',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
          productId: id,
          product_page: 'Storefront Page',
        },
      });
    }
  }

  function closeDropdown() {
    setCurrentDropdown(null);
  }
  const image = getPrimaryImage(images);
  return (
    <li>
      <div className="product-item-left">
        <img src={image} width={28} height={28} alt={`${product_name} image`} />
        <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
          {product_name}
        </Text>
      </div>
      <div className={`product-item-right ${discounted_amount ? 'discount-applicable' : ''}`}>
        <Text
          color={discounted_amount ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
          variant="body"
          size="medium"
          weight="regular"
        >
          {formatTextAmountField(amount)}
        </Text>
        {discounted_amount && (
          <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
            {formatTextAmountField(discounted_amount)}
          </Text>
        )}
        <IconWrapper
          onClick={() => setCurrentDropdown(id)}
          ref={ref}
          className="storefront-options"
        >
          <MoreHorizontalIcon color="interactive.icon.gray.normal" size="small" />
          {/* handling state in parent, as only one dropdown can be open at a time */}
          {isDropdownOpen ? (
            !isMobile ? (
              <OptionsDropdownWrapper>
                {editProduct && (
                  <div onClick={() => handleEdit(id)}>
                    <EditComposeIcon color="interactive.icon.primary.subtle" size="small" /> Edit
                  </div>
                )}
                {removeProduct && (
                  <div className="danger" onClick={() => handleRemove(id)}>
                    <TrashIcon color="feedback.icon.negative.intense" size="small" /> Remove
                  </div>
                )}
              </OptionsDropdownWrapper>
            ) : (
              <BottomSheet isOpen={true} isControlled onDismiss={closeDropdown}>
                <BottomSheetWrapper>
                  {editProduct && (
                    <BottomSheetItem onClick={() => handleEdit(id)} gap="12px">
                      <EditIcon color="feedback.icon.neutral.intense" size="medium" />{' '}
                      <span>Edit</span>
                    </BottomSheetItem>
                  )}
                  {removeProduct && (
                    <BottomSheetItem onClick={() => handleRemove(id)} gap="12px">
                      <TrashIcon color="feedback.icon.negative.intense" size="medium" />{' '}
                      <span className="pp-danger-text">Remove</span>
                    </BottomSheetItem>
                  )}
                </BottomSheetWrapper>
              </BottomSheet>
            )
          ) : null}
        </IconWrapper>
      </div>
    </li>
  );
};

const ProductItems = ({
  data,
  editProduct,
  removeProduct,
  isMobile,
}: IProductItems): React.ReactElement => {
  const [currentDropdown, setCurrentDropdown] = useState<TDropdown>(null);

  const openDropdown = (id: TDropdown) => setCurrentDropdown(id);
  return (
    <ProductItemsWrapper>
      {data.map((item) => (
        <ProductItem
          product={item}
          key={item.id}
          currentDropdown={currentDropdown}
          setCurrentDropdown={openDropdown}
          editProduct={editProduct}
          removeProduct={removeProduct}
          isMobile={isMobile}
        />
      ))}
    </ProductItemsWrapper>
  );
};

const ProductSection = ({
  title = 'Products',
  data,
  children,
  className,
  editProduct,
  removeProduct,
  isMobile,
}: IProductSection): React.ReactElement => {
  return (
    <ProductsWrapper className={className}>
      <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
        {title}
      </Text>
      <ProductItems
        data={data}
        editProduct={editProduct}
        removeProduct={removeProduct}
        isMobile={isMobile}
      />
      {children}
    </ProductsWrapper>
  );
};

export default ProductSection;

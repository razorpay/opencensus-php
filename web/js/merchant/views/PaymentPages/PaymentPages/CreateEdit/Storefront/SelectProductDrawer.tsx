import React, { useEffect, useState } from 'react';
import { Button, Checkbox, Heading, PlusIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  addProducts,
  fetchProducts,
  IPaymentPagesProduct,
} from 'merchant/reducers/paymentPages/storefront';
import { SelectProductSkeleton } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/styled';
import { showNotification } from 'merchant_common/reducers/notifications';

import CheckboxItem from './CheckboxItem';
import { AddFooterWrapper, SelectCheckboxContainer, SelectProductDrawerWrapper } from './styled';
import { ICheckbox, ISelectProductDrawer } from './types';
import { generateCheckboxesFromAllProducts } from './utils';

const SelectProductDrawer = ({
  handleClose,
  storefront,
  fetchProducts,
  addProducts,
  openAddModal,
  showNotification,
}: ISelectProductDrawer) => {
  const [checkboxes, setCheckboxes] = useState<Array<ICheckbox>>([]);

  useEffect(() => {
    fetchAllProducts();
  }, []);

  useEffect(() => {
    const newCheckboxes: Array<ICheckbox> = generateCheckboxesFromAllProducts(
      storefront.allProducts.data,
      storefront.entity.products,
    );
    setCheckboxes(newCheckboxes);
  }, [storefront.allProducts, storefront.entity.products]);

  function fetchAllProducts() {
    fetchProducts(500);
  }

  const onProductAdd = () => {
    const { allProducts } = storefront;
    const newlyAddedProductsIds: Array<string> = [];
    for (let i = 0; i < checkboxes.length; i++) {
      const item = checkboxes[i];
      if (item.checked && !item.disabled) {
        newlyAddedProductsIds.push(item.id);
      }
    }
    const newProducts = newlyAddedProductsIds.map((currentId) => {
      // TODO: it will never return undefined, as we use the same object initially. Need to fix this TS error
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      const foundProduct: IPaymentPagesProduct = allProducts.data.find((p) => p.id === currentId);
      return foundProduct;
    });
    // update redux & close modal
    addProducts(newProducts);
    showNotification({
      type: 'success',
      message: 'Products have been added',
    });
    handleClose();
  };

  const onChange = (e: { target: { name: string; value: string } }) => {
    // TODO: Temporarily using the old checkbox as blade checkbox has open issues with a scrollbar
    const { name: id, value } = e.target;
    const isChecked = value === '1';
    if (id) {
      setCheckboxes((prevCheckboxes) => {
        const newCheckboxes = prevCheckboxes.map((item) => {
          const isFound = item.id === id;
          if (isFound) {
            return {
              ...item,
              checked: isChecked,
            };
          }
          return item;
        });
        return newCheckboxes;
      });
    }
  };

  const { allProducts } = storefront;
  const newlySelectedCount = checkboxes.reduce(
    (acc, item) => acc + (item.checked && !item.disabled ? 1 : 0),
    0,
  );
  const selectedCount = checkboxes.reduce((acc, item) => acc + (item.checked ? 1 : 0), 0);
  const isAllChecked = selectedCount === checkboxes.length;
  const isIndeterminate = selectedCount > 0 && !isAllChecked;
  // const isNoneSelected = selectedCount < 1;
  let footerButtons;
  if (newlySelectedCount === 0) {
    footerButtons = null;
  } else {
    footerButtons = (
      <>
        <Button size="medium" type="button" variant="secondary" key="cancel" onClick={handleClose}>
          Cancel
        </Button>
        <Button
          size="medium"
          type="button"
          variant="primary"
          key="submit"
          onClick={onProductAdd}
          isDisabled={allProducts.loading}
        >
          {newlySelectedCount === 1 ? 'Add 1 product' : `Add ${newlySelectedCount} products`}
        </Button>
      </>
    );
  }

  const onClickHandler = () => {
    openAddModal();
    analyticsTrack({
      objectName: 'Add product',
      actionName: 'Clicked',
      screen: 'Products Screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        screen_source: 'store_view',
      },
    });
  };

  return (
    <SelectProductDrawerWrapper
      maskClosable={false}
      onClose={handleClose}
      footerButtons={footerButtons}
    >
      <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
        Add products to page
      </Heading>
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
        Choose from your existing products or add a new product
      </Text>
      <SelectCheckboxContainer data-testid="select-checkbox-container">
        {allProducts.loading ? (
          <SelectProductSkeleton />
        ) : !allProducts.error ? (
          <>
            {checkboxes.length > 0 && (
              <Checkbox
                isChecked={isAllChecked}
                onChange={({ isChecked }) => {
                  if (isChecked) {
                    setCheckboxes((prevCheckboxes) =>
                      prevCheckboxes.map((item) => ({
                        ...item,
                        checked: true,
                      })),
                    );
                    return;
                  }
                  setCheckboxes((prevCheckboxes) =>
                    prevCheckboxes.map((item) => ({
                      ...item,
                      // preserve state for disabled items, set the rest fields to unchecked
                      checked: item.disabled ? item.checked : false,
                    })),
                  );
                }}
                isIndeterminate={newlySelectedCount !== 0 && isIndeterminate}
              >
                Select all
              </Checkbox>
            )}
            {checkboxes.map((item) => (
              <CheckboxItem {...item} key={item.id} onChange={onChange} />
            ))}
          </>
        ) : (
          'Something went wrong. Try again later'
        )}
      </SelectCheckboxContainer>
      {newlySelectedCount === 0 && <SelectProductDrawerAddProductFooter onClick={onClickHandler} />}
    </SelectProductDrawerWrapper>
  );
};

const mapStateToProps = (state) => ({
  storefront: state.paymentPageStorefront,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchProducts,
      addProducts,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SelectProductDrawer);

function SelectProductDrawerAddProductFooter({
  onClick,
}: {
  onClick: () => void;
}): React.ReactElement {
  return (
    <AddFooterWrapper>
      <span>--- OR ---</span>
      <Button variant="primary" icon={PlusIcon} iconPosition="left" onClick={onClick}>
        Add a new product
      </Button>
    </AddFooterWrapper>
  );
}

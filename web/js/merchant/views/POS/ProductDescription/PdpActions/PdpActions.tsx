import React, { useContext, useRef } from 'react';
import { Amount, ArrowRightIcon, Box, Button, Text } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import QuantityWidget from 'merchant/views/POS/Cart/QuantityWidget';
import { ACTIONS, UPDATE_CART_ACTIONS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import {
  getProductFromCart,
  getProductFromProductDescriptions,
  updateCart,
} from 'merchant/views/POS/helpers';
import { useBladeBreakpoints, useIsVisible } from 'merchant/views/POS/hooks';
import { ProductPlans, Product as ProductType, ProductUpdateTypes } from 'merchant/views/POS/types';

type PdpActionsProps = {
  productCode: string;
  plan: ProductPlans;
  containerRef: HTMLElement | null;
};

const PdpActions = ({ productCode, plan, containerRef }: PdpActionsProps): JSX.Element | null => {
  const { isLargeScreen, isMobile } = useBladeBreakpoints();
  const mainActionsRef = useRef<HTMLElement>(null);
  const isActionsVisible = useIsVisible({ ref: mainActionsRef, initialValue: true });
  const navigate = useNavigate();
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { cartItems, productDescriptions, isCartOpen } = state;
  const product = {
    productCode,
    plan,
  };
  const cartItem = getProductFromCart({ cart: cartItems, product });
  const productDescription = getProductFromProductDescriptions({
    code: productCode,
    productDescriptions,
  });

  if (!productDescription) return null;

  const onProductQuantityUpdate = (updateType: ProductUpdateTypes, product: ProductType) => {
    const newCartItems = updateCart({
      cart: cartItems,
      type:
        updateType === 'add'
          ? UPDATE_CART_ACTIONS.INCREASE_QUANTITY
          : UPDATE_CART_ACTIONS.DECREASE_QUANTITY,
      product,
    });
    dispatch({
      type: ACTIONS.UPDATE_CART,
      payload: {
        cartItems: newCartItems,
      },
    });
  };

  const handleProceedtoCheckoutClick = () => {
    navigate(`/pos/catalog/${productCode}/order-summary`);
  };

  const plans = productDescription.pricing.find(({ type }) => type === plan);
  const pricingBreakups = plans?.breakups || [];

  const renderCartActionButtons = (isFloatingWidget = false) => (
    <Box
      display="flex"
      alignItems="center"
      height="50px"
      testID={isFloatingWidget ? 'floating-widget-actions' : 'pdp-actions'}
    >
      {cartItem ? (
        <QuantityWidget
          cartItem={cartItem}
          onProductQuantityUpdate={onProductQuantityUpdate}
          size="large"
          isMinZero
        />
      ) : (
        <AddToCartButton productCode={productCode} plan={plan} />
      )}
      <Box flexGrow={1} marginLeft="spacing.4">
        <Button
          size="large"
          icon={ArrowRightIcon}
          iconPosition="right"
          onClick={handleProceedtoCheckoutClick}
          isDisabled={!cartItem}
          testID="proceed-to-checkout"
          isFullWidth={isMobile}
        >
          {isMobile ? 'Proceed' : 'Proceed to Order'}
        </Button>
      </Box>
    </Box>
  );

  const boundingRect = containerRef?.getBoundingClientRect?.();

  return (
    <React.Fragment>
      <Box ref={mainActionsRef}>{renderCartActionButtons()}</Box>
      {!isActionsVisible && isLargeScreen && !isCartOpen ? (
        <Box
          position="fixed"
          bottom="30px"
          display="flex"
          justifyContent="center"
          left={`${boundingRect?.left ?? 0}px`}
          right="0px"
          zIndex={100}
          width={`${boundingRect?.width ?? 0}px`}
          testID="pdp-floating-actions"
        >
          <Box
            display="flex"
            backgroundColor="surface.background.gray.intense"
            elevation="highRaised"
            paddingY="spacing.4"
            paddingX="spacing.5"
            zIndex={100}
            borderRadius="large"
            justifyContent="space-between"
            alignItems="center"
            gap="spacing.6"
            minWidth="750px"
          >
            <Box>
              <Text size="large">{productDescription.productTitle}</Text>
              <Box display="flex" alignItems="center">
                {pricingBreakups.map(({ key, value, description }, index) => (
                  <Box
                    key={key}
                    display="flex"
                    alignItems="center"
                    justifyContent="space-between"
                    marginBottom="spacing.4"
                  >
                    {index > 0 ? <Text marginX="spacing.2">+</Text> : null}
                    <Box display="flex" alignItems="center">
                      <Amount
                        value={value}
                        suffix="none"
                        isAffixSubtle={false}
                        marginRight="spacing.2"
                        type="body"
                        size="large"
                        weight="semibold"
                      />
                      <Text size="large">{description}</Text>
                    </Box>
                  </Box>
                ))}
              </Box>
            </Box>
            {renderCartActionButtons(true)}
          </Box>
        </Box>
      ) : null}
    </React.Fragment>
  );
};
export default PdpActions;

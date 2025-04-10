import React, { Fragment } from 'react';
import { Box, Card, CardBody, Divider, Heading, Spinner, Text } from '@razorpay/blade/components';

import DigitalBillingAdditionalDetails from 'merchant/views/StoreSettings/StoreCreateOrEdit/containers/RzpProductsAdditionalDetails';
import { useStoresCreateStore } from 'merchant/views/StoreSettings/StoreCreateOrEdit/stores/storesCreateFormStore';

type StoreLinkedProductsProps = {
  isLoading: boolean;
};

const StoreLinkedProducts = (props: StoreLinkedProductsProps) => {
  const { isLoading } = props;
  const { basicInfoForm } = useStoresCreateStore();
  return (
    <Card data-analytics-name="store-linked-products-section">
      <CardBody>
        <Heading>Link Razorpay Products Additional Details</Heading>
        {isLoading ? (
          <Box display="flex" alignItems="center" justifyContent="center" height="100%">
            <Spinner accessibilityLabel="Basic info loading" />
          </Box>
        ) : (
          <Fragment>
            <Text marginTop="spacing.8">
              {`Products linked to your MID will be shown here. Turn 'ON' the products you want to link to your store.`}
            </Text>
            <Divider dividerStyle="dashed" marginY="spacing.8" />
            {basicInfoForm?.linkedProducts?.includes('DIGITAL_BILLING') ? (
              <DigitalBillingAdditionalDetails />
            ) : null}
            {basicInfoForm?.linkedProducts?.length === 0 ? <Text>No products linked.</Text> : null}
          </Fragment>
        )}
      </CardBody>
    </Card>
  );
};

export default StoreLinkedProducts;

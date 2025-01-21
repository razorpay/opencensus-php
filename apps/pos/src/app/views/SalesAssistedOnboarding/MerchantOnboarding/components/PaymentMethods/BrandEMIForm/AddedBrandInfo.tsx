import React from 'react';
import { Box, Button, Divider, Heading, PlusIcon, Text } from '@razorpay/blade/components';
import BrandInfoCard from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandInfoCard';
import { Brand } from 'apps/pos/src/app/utils/paymentsAndServices';

export interface AddedBrandInfoProps {
  storeType: string;
  submitHandler: () => void;
  addBrandHandler: () => void;
  removeBrandHandler: (brandName: string) => void;
  brands: Brand[];
  isUpdateModularLoading: boolean;
  isFormDisabled: boolean;
  isBrandEmiCcDcEnabled: boolean;
}

const AddedBrandInfo = ({
  addBrandHandler,
  brands,
  storeType,
  submitHandler,
  isUpdateModularLoading,
  removeBrandHandler,
  isFormDisabled,
  isBrandEmiCcDcEnabled,
}: AddedBrandInfoProps): JSX.Element => {
  return (
    <Box>
      <Heading marginBottom="spacing.7" size="medium" weight="semibold">
        Brand Information Form
      </Heading>
      <Box
        marginBottom={'spacing.5'}
        gap="spacing.6"
        display="flex"
        justifyContent="flex-sart"
        alignItems="center"
      >
        <Text>Type of Store</Text>
        <Text weight="semibold">{storeType}</Text>
      </Box>
      <Divider marginBottom="spacing.7" height={'spacing.1'} />
      <Box marginBottom={'spacing.5'}>
        {brands?.map((brand) => (
          <Box key={brand.verificationDetailsId} marginBottom={'spacing.5'}>
            <BrandInfoCard
              isUpdateModularLoading={isUpdateModularLoading}
              brand={brand}
              removeBrandHandler={removeBrandHandler}
              isFormDisabled={isFormDisabled || !isBrandEmiCcDcEnabled}
            />
          </Box>
        ))}
      </Box>
      <Box
        backgroundColor="surface.background.gray.intense"
        padding="spacing.5"
        display="flex"
        position="fixed"
        gap={'spacing.4'}
        left="0px"
        right="0px"
        bottom="0px"
        elevation="highRaised"
        alignItems="center"
        width="100%"
        zIndex={1}
      >
        <Button
          icon={PlusIcon}
          iconPosition="left"
          isFullWidth
          variant="tertiary"
          color="primary"
          size="medium"
          onClick={addBrandHandler}
          isLoading={isUpdateModularLoading}
          isDisabled={isFormDisabled || !isBrandEmiCcDcEnabled}
        >
          Add New Brand
        </Button>
        <Button onClick={submitHandler} variant="primary" isFullWidth isDisabled={false}>
          Save All
        </Button>
      </Box>
    </Box>
  );
};

export default AddedBrandInfo;

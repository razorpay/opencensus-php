import React, { useState, useEffect, Fragment } from 'react';
import {
  Box,
  Card,
  CardBody,
  Divider,
  Heading,
  Text,
  Switch,
  Dropdown,
  AutoComplete,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Spinner,
  DropdownFooter,
  Link,
  ArrowRightIcon,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useFormikContext } from 'formik';
import { useNavigate } from 'react-router-dom';

import { graphqlRequest } from 'common/services/graphql/graphql-client';
import DisableLinkedProductAlertModal from 'merchant/views/StoreSettings/StoreCreateOrEdit/components/DisableLinkedProductAlertModal';
import { BRANDS_TABLE_DATA_QUERY } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/queries';
import { useStoresCreateStore } from 'merchant/views/StoreSettings/StoreCreateOrEdit/stores/storesCreateFormStore';
import { StoreCreateFormValues } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';
import { DIGITAL_BILLING, MAX_LIMIT } from 'merchant/views/StoreSettings/common/constants';

type LinkRzpProductsProps = {
  isLoading: boolean;
};

const LinkRzpProducts = (props: LinkRzpProductsProps) => {
  const { isLoading } = props;
  const {
    values: formValues,
    touched,
    errors,
    setFieldValue,
  } = useFormikContext<StoreCreateFormValues>();
  const [searchTerm, setSearchTerm] = useState('');
  const [shouldShowAlertModal, setShouldShowAlertModal] = useState(false);
  const { basicInfoForm } = useStoresCreateStore();
  const navigate = useNavigate();

  const { isFetching, data } = useQuery({
    queryKey: ['brands_dropdown'],
    queryFn: async () =>
      graphqlRequest({
        document: BRANDS_TABLE_DATA_QUERY,
        variables: { searchTerm, limit: MAX_LIMIT, offset: 0 },
      }),
    retry: false,
    refetchOnWindowFocus: false,
  });

  const brands = data?.storeBrands?.storeBrands || [];
  const isDigitalBillingSelected = formValues?.linkedProducts?.includes(DIGITAL_BILLING);

  useEffect(() => {
    setSearchTerm(basicInfoForm?.digitalBilling?.brandName || '');
  }, [basicInfoForm]);

  const handleLinkedProductDisablement = () => {
    setFieldValue(
      'linkedProducts',
      formValues?.linkedProducts?.filter((val) => val !== DIGITAL_BILLING) || [],
    );
    setFieldValue('digitalBilling', {});
    setSearchTerm('');
    setShouldShowAlertModal(false);
  };

  return (
    <>
      {shouldShowAlertModal && (
        <DisableLinkedProductAlertModal
          onSubmit={handleLinkedProductDisablement}
          modalProps={{
            isOpen: shouldShowAlertModal,
            onDismiss: () => setShouldShowAlertModal(false),
          }}
        />
      )}
      <Card data-analytics-name="store-linked-products-section">
        <CardBody>
          <Heading>Link Razorpay Products</Heading>
          {isLoading ? (
            <Box display="flex" alignItems="center" justifyContent="center" height="100%">
              <Spinner accessibilityLabel="Location & Store Contact loading" />
            </Box>
          ) : (
            <Fragment>
              <Text marginTop="spacing.5">
                Products linked to your MID will be shown here. Turn 'ON' the products you want to
                link to your store.
              </Text>
              <Divider dividerStyle="dashed" marginY="spacing.8" />
              <Box width="70%" display="flex" gap="spacing.4" flexDirection="column">
                <Box display="flex" gap="spacing.3">
                  <Heading>Digital Billing</Heading>
                  <Switch
                    key={formValues?.digitalBilling?.brandId}
                    isChecked={isDigitalBillingSelected}
                    onChange={({ isChecked }) => {
                      if (isChecked) {
                        setFieldValue('linkedProducts', [
                          ...(formValues.linkedProducts || []),
                          DIGITAL_BILLING,
                        ]);
                      } else {
                        setShouldShowAlertModal(true);
                      }
                    }}
                    accessibilityLabel="Digital Billing"
                  />
                </Box>
                <Dropdown>
                  <AutoComplete
                    isDisabled={!isDigitalBillingSelected}
                    inputValue={searchTerm}
                    onChange={({ values }) => {
                      setFieldValue('digitalBilling.brandId', values[0]);
                    }}
                    onInputValueChange={({ value = '' }) => {
                      setSearchTerm(value);
                    }}
                    label="Brand"
                    labelPosition="left"
                    placeholder="Select Brand"
                    necessityIndicator={isDigitalBillingSelected ? 'required' : 'none'}
                    validationState={
                      touched.digitalBilling?.brandId && errors?.digitalBilling?.brandId
                        ? 'error'
                        : 'none'
                    }
                    errorText={errors?.digitalBilling?.brandId || ''}
                  />
                  <DropdownOverlay>
                    {isFetching ? (
                      <Box display="flex" justifyContent="center" padding="spacing.4">
                        <Spinner accessibilityLabel="Fetching Brands" />
                      </Box>
                    ) : (
                      <ActionList>
                        {brands.map((brand) => (
                          <ActionListItem title={brand.name} value={brand.id} key={brand.id} />
                        ))}
                      </ActionList>
                    )}
                    <DropdownFooter>
                      <Link
                        icon={ArrowRightIcon}
                        iconPosition="right"
                        onClick={() => navigate('/app/billme-settings/brands-and-terminals')}
                      >
                        Create New Brand
                      </Link>
                    </DropdownFooter>
                  </DropdownOverlay>
                </Dropdown>
              </Box>
            </Fragment>
          )}
        </CardBody>
      </Card>
    </>
  );
};

export default LinkRzpProducts;

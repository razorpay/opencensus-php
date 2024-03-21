import React from 'react';
import {
  Box,
  TextInput,
  TextArea,
  Button,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  Heading,
  Link,
  BottomSheetHeader,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { FormikErrors, FormikValues, useFormik } from 'formik';

import { useSplitzService } from 'common/splitz';
import { states as STATES } from 'merchant/helpers/data';
import { deliveryAddressSchema, pincodeValidationSchema } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { DeliveryAddress } from 'merchant/views/POS/types';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { checkIfPanIndiaLive } from 'merchant/views/POS/helpers';

type AddressFormProps = {
  isEdit: boolean;
  isBottomSheetOpen?: boolean;
  deliveryAddress?: Omit<DeliveryAddress, 'isSelected'> | null;
  onCancelClick: () => void;
  onSubmit: (values) => void;
};

type AddressFormFieldsProps = {
  values: FormikValues;
  errors: FormikErrors<DeliveryAddress>;
  isEdit: boolean;
  isMobile: boolean;
  isLoading: boolean;
  handleChange: (name, value, fieldType) => void;
  onCancelClick: () => void;
};

const AddressFormFields = ({
  values,
  errors,
  isEdit,
  isMobile,
  isLoading,
  handleChange,
  onCancelClick,
}: AddressFormFieldsProps): JSX.Element => {
  const renderAddressOption = (state): JSX.Element => (
    <ActionListItem
      key={state}
      title={STATES[state]}
      value={state}
      testID={`${STATES[state]}-option`}
    />
  );

  return (
    <React.Fragment>
      {!isMobile ? (
        <Box
          display="flex"
          alignItems="center"
          justifyContent="space-between"
          marginBottom="spacing.4"
        >
          <Heading>{isEdit ? 'Edit' : 'Add New'} Address</Heading>
          <Link variant="button" onClick={onCancelClick}>
            Cancel
          </Link>
        </Box>
      ) : null}
      <Box display={{ base: 'block', l: 'grid' }} gridTemplateColumns="1fr 1fr" gap="spacing.3">
        <TextInput
          label="Full Name"
          placeholder="Enter Name"
          name="name"
          value={values.name}
          necessityIndicator="required"
          onChange={({ name, value }) => handleChange(name, value, 'Text Box')}
          marginBottom={{ base: 'spacing.5', l: 'spacing.3' }}
          validationState={!!errors?.name ? 'error' : 'none'}
          errorText={errors?.name as string}
        />
        <TextInput
          label="Mobile Number"
          placeholder="Enter Mobile Number"
          name="phoneNumber"
          value={values.phoneNumber}
          necessityIndicator="required"
          onChange={({ name, value }) => handleChange(name, value, 'Text Box')}
          maxCharacters={10}
          marginBottom={{ base: 'spacing.5', l: 'spacing.3' }}
          validationState={!!errors?.phoneNumber ? 'error' : 'none'}
          errorText={errors?.phoneNumber as string}
        />
      </Box>
      <Box
        display={{ base: 'block', l: 'grid' }}
        gridTemplateColumns="1fr 1fr 1fr"
        gap="spacing.3"
        alignItems="baseline"
      >
        <TextInput
          label="Pincode"
          placeholder="Enter Pincode"
          name="pincode"
          value={values.pincode}
          necessityIndicator="required"
          onChange={({ name, value }) => handleChange(name, value, 'Text Box')}
          marginBottom={{ base: 'spacing.5', l: 'spacing.3' }}
          validationState={!!errors?.pincode ? 'error' : 'none'}
          errorText={errors?.pincode as string}
          maxCharacters={6}
        />
        <TextInput
          label="City"
          placeholder="Enter City"
          name="city"
          value={values.city}
          necessityIndicator="required"
          onChange={({ name, value }) => handleChange(name, value, 'Text Box')}
          marginBottom="spacing.5"
          validationState={!!errors?.city ? 'error' : 'none'}
          errorText={errors?.city as string}
        />
        <Dropdown selectionType="single" marginBottom={{ base: 'spacing.5', l: 'spacing.0' }}>
          <SelectInput
            label="State"
            name="state"
            value={values.state}
            placeholder="Select a state"
            onChange={({ name, values }) => handleChange(name, values[0], 'Dropdown')}
            necessityIndicator="required"
            validationState={!!errors?.state ? 'error' : 'none'}
            errorText={errors?.state as string}
          />
          {isMobile ? (
            <BottomSheet>
              <BottomSheetBody>
                <ActionList>
                  {Object.keys(STATES).map((state) => renderAddressOption(state))}
                </ActionList>
              </BottomSheetBody>
            </BottomSheet>
          ) : (
            <DropdownOverlay>
              <ActionList>
                {Object.keys(STATES).map((state) => renderAddressOption(state))}
              </ActionList>
            </DropdownOverlay>
          )}
        </Dropdown>
      </Box>
      <Box>
        <TextArea
          name="address"
          label="Address"
          value={values.address}
          necessityIndicator="required"
          placeholder="Enter Address"
          maxCharacters={100}
          marginBottom={{ base: 'spacing.5', l: 'spacing.0' }}
          onChange={({ name, value }) => handleChange(name, value, 'Text Box')}
          validationState={!!errors?.address ? 'error' : 'none'}
          errorText={errors?.address as string}
        />
      </Box>
      {!isMobile ? (
        <Button type="submit" isLoading={isLoading}>
          Save Address
        </Button>
      ) : null}
    </React.Fragment>
  );
};

const AddressForm = (props: AddressFormProps): JSX.Element => {
  const { onSubmit, deliveryAddress, isBottomSheetOpen, isEdit, onCancelClick } = props;
  const { name, phoneNumber, address, city, pincode, state } = deliveryAddress ?? {};

  const { abExperiments } = useSplitzService();
  const { omniChannelGtm } = abExperiments ?? {};
  const gtmCities = omniChannelGtm?.variables?.cities;
  const availableCities = typeof gtmCities === 'string' ? gtmCities.split(',') : [];

  const isPANIndiaLive = checkIfPanIndiaLive({ abExperiments });

  const formik = useFormik<FormikValues>({
    initialValues: {
      name: name ?? '',
      phoneNumber: phoneNumber ?? '',
      address: address ?? '',
      pincode,
      city,
      state,
    },
    validationSchema: deliveryAddressSchema.shape(
      pincodeValidationSchema(availableCities, isPANIndiaLive),
    ),
    validateOnChange: false,
    enableReinitialize: true,
    onSubmit: (values) => onSubmit({ ...values, type: 'custom' }),
  });

  const { errors, values, setFieldValue, handleSubmit } = formik;
  const { isMobile } = useBladeBreakpoints();

  const handleChange = (name, value, fieldType) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.formFieldFillInitiated, {
      formName: isEdit ? 'Edit Address' : 'Add New Address',
      fieldName: name,
      fieldType,
      section: isEdit ? 'Pre-checkout - Edit Address' : 'Pre-checkout - Add New Address',
      subSection: isEdit ? 'Pre-checkout - Edit Address' : 'Pre-checkout - Add New Address',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: isEdit ? 'Pre-checkout - Edit Address' : 'Pre-checkout - Add New Address',
    });

    const numberFields = ['phoneNumber', 'pincode'];
    if (name) {
      if (numberFields.includes(name) && isNaN(Number(value))) {
        return;
      }
      setFieldValue(name, value);
    }
  };

  return isMobile ? (
    <form onSubmit={handleSubmit}>
      <BottomSheet
        isOpen={isBottomSheetOpen}
        onDismiss={onCancelClick}
        snapPoints={[0.75, 0.8, 1.0]}
      >
        <BottomSheetHeader title={`${isEdit ? 'Edit' : 'Add New'} Address`} />
        <BottomSheetBody>
          <AddressFormFields
            errors={errors}
            values={values}
            isMobile={isMobile}
            handleChange={handleChange}
            isLoading={formik.isValidating}
            {...props}
          />
        </BottomSheetBody>
        <BottomSheetFooter>
          <Button type="submit" isFullWidth size="large" isLoading={formik.isValidating}>
            Save Address
          </Button>
        </BottomSheetFooter>
      </BottomSheet>
    </form>
  ) : (
    <Box width="100%">
      <form onSubmit={handleSubmit}>
        <AddressFormFields
          errors={errors}
          values={values}
          isMobile={isMobile}
          handleChange={handleChange}
          isLoading={formik.isValidating}
          {...props}
        />
      </form>
    </Box>
  );
};

export default AddressForm;

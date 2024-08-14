import React, { useState } from 'react';
import {
  Box,
  Heading,
  Checkbox,
  TextInput,
  Link,
  EditIcon,
  Button,
  ArrowRightIcon,
  useToast,
  Text,
} from '@razorpay/blade/components';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import {
  AggregatorModelForm,
  DirectModelForm,
  PaymentMethodFormType,
  PaymentMethodsFieldKeyNames,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  AggregatorModelFormKeys,
  DirectModelFormKeys,
} from 'apps/pos/src/app/constants/PaymentsAndService';

export type PaymentMethodForm = {
  type: PaymentMethodFormType;
  form: DirectModelForm | AggregatorModelForm;
};

export interface PaymentMethodFormProps {
  methodForm: PaymentMethodForm;
  onFileUploadChange: (files: FileItem[]) => void;
  onFieldCheckboxChange: (key: string) => void;
  onFieldInputChange: (key: string, value: any) => void;
  onFormSubmitClick: () => void;
}

const PaymentMethodFormComponent: React.FC<PaymentMethodFormProps> = ({
  methodForm,
  onFileUploadChange,
  onFieldCheckboxChange,
  onFieldInputChange,
  onFormSubmitClick,
}) => {
  const { form } = methodForm;
  const toast = useToast();
  const [isMDREditEnabled, setIsMDREditEnabled] = useState(false);
  const [isVASEditEnabled, setIsVASEditEnabled] = useState(false);

  const onEditVASClick = () => {
    setIsVASEditEnabled((prev) => !prev);
  };

  const onEditMDRClick = () => {
    setIsMDREditEnabled((prev) => !prev);
  };

  const isAggregatorFieldsPresent =
    Object.keys(form).filter((key) =>
      AggregatorModelFormKeys.includes(key as keyof AggregatorModelForm),
    ).length > 0;

  const getDefaultFiles = (form) => {
    if (!form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value) return [];
    if (form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value === '0') return [];
    return form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value;
  };

  return (
    <Box maxWidth="768px" padding="spacing.5" height="80vh" overflow="scroll">
      <Box display="flex" flexDirection="row" justifyContent="space-between">
        <Heading marginBottom="spacing.5" size="large">
          Choose {isAggregatorFieldsPresent && `MDR Rates & `}Value Added Services
        </Heading>
        {!isAggregatorFieldsPresent ? (
          <Link
            iconPosition="left"
            icon={!isVASEditEnabled ? EditIcon : undefined}
            onClick={onEditVASClick}
            alignSelf="center"
          >
            {isVASEditEnabled ? 'Save Changes' : 'Edit'}
          </Link>
        ) : null}
      </Box>
      {Object.keys(form).filter((key) =>
        [
          PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD,
          PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD,
        ].includes(key as PaymentMethodsFieldKeyNames),
      ).length ? (
        <Box>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="flex-start"
            marginBottom="spacing.5"
          >
            <Checkbox
              name={PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD}
              size="medium"
              isChecked={
                form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD].value as boolean
              }
              helpText={form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD].description}
              onChange={() =>
                onFieldCheckboxChange(PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD)
              }
            >
              {form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD].title}
            </Checkbox>
          </Box>
          <SalesFileUpload
            name={PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD}
            label="Upload custom rates proof"
            accept=".pdf"
            uploadType="multiple"
            onChange={onFileUploadChange}
            maxSize={5 * 1024 * 1023}
            maxLimit={5}
            isLoading={false}
            defaultValue={getDefaultFiles(form)}
            onError={() =>
              toast.show({
                content: `Some Error Occured while upload file. Please try again later`,
                color: 'negative',
              })
            }
            isDisabled={!form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD].value}
          />
        </Box>
      ) : null}
      {isAggregatorFieldsPresent ? (
        <>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            marginBottom="spacing.5"
          >
            <Heading size="medium">MDR Rates</Heading>
            <Link
              iconPosition="left"
              icon={!isMDREditEnabled ? EditIcon : undefined}
              onClick={onEditMDRClick}
              alignSelf="center"
            >
              {isMDREditEnabled ? 'Save Changes' : 'Edit'}
            </Link>
          </Box>
          <Box>
            {Object.keys(form)
              .filter((key) => AggregatorModelFormKeys.includes(key as keyof AggregatorModelForm))
              .map((key, i) => {
                const field = form[key];
                return (
                  <Box
                    key={`${key}_${i}`}
                    display="flex"
                    flexDirection="row"
                    justifyContent="space-between"
                    marginBottom="spacing.5"
                  >
                    <Box
                      display={'flex'}
                      flexDirection={'column'}
                      gap={'spacing.1'}
                      justifyContent={'center'}
                      alignItems={'flex-start'}
                    >
                      <Text color="surface.text.gray.subtle"> {field.title} </Text>
                      <Text color="surface.text.gray.muted" variant="caption">
                        {field.description}
                      </Text>
                    </Box>
                    <Box width="spacing.11" minWidth={'80px'} maxWidth={'120px'}>
                      {isMDREditEnabled ? (
                        <TextInput
                          type="number"
                          size="medium"
                          label=""
                          isDisabled={!isMDREditEnabled}
                          value={field.value}
                          onChange={(e) => onFieldInputChange(key, e.value)}
                          suffix={'%'}
                        />
                      ) : (
                        <Text color="surface.text.gray.subtle" textAlign="right">
                          {field.value || Number(field.defaultValue)} %
                        </Text>
                      )}
                    </Box>
                  </Box>
                );
              })}
          </Box>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            marginBottom="spacing.5"
          >
            <Heading size="medium">Affordability Category</Heading>
            <Link
              iconPosition="left"
              icon={!isVASEditEnabled ? EditIcon : undefined}
              onClick={onEditVASClick}
              alignSelf="center"
            >
              {isVASEditEnabled ? 'Save Changes' : 'Edit'}
            </Link>
          </Box>
        </>
      ) : null}
      {Object.keys(form)
        .filter((key) => DirectModelFormKeys.includes(key as keyof DirectModelForm))
        .map((key, i) => {
          const field = form[key];
          return (
            <Box
              key={`${key}_${i}`}
              display="flex"
              flexDirection="row"
              justifyContent="space-between"
              marginBottom="spacing.5"
            >
              <Checkbox
                name={key}
                size="medium"
                helpText={field.description}
                isChecked={field.value}
                onChange={() => onFieldCheckboxChange(key)}
              >
                {field.title}
              </Checkbox>
              <Box width="spacing.11" minWidth={'80px'} maxWidth={'120px'}>
                {isVASEditEnabled ? (
                  <TextInput
                    size="medium"
                    label=""
                    isDisabled={!isVASEditEnabled}
                    value={field.value}
                    onChange={(e) => onFieldInputChange(key, e.value)}
                    suffix="%"
                  />
                ) : (
                  <Text color="surface.text.gray.subtle" textAlign="right">
                    {field.value || Number(field.defaultValue)} %
                  </Text>
                )}
              </Box>
            </Box>
          );
        })}
      <Box
        backgroundColor="surface.background.gray.intense"
        padding="spacing.5"
        display="flex"
        position="fixed"
        left="0px"
        right="0px"
        bottom="0px"
        elevation="highRaised"
        alignItems="center"
        width="100%"
        zIndex={1}
      >
        <Button
          onClick={onFormSubmitClick}
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="primary"
          isFullWidth
          isDisabled={isMDREditEnabled || isVASEditEnabled}
        >
          Save & Continue
        </Button>
      </Box>
    </Box>
  );
};

export default PaymentMethodFormComponent;

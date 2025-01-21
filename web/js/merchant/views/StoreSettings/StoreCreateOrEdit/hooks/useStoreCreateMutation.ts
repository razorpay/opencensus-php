import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { STORE_CREATE } from 'merchant/views/StoreSettings/StoreCreateOrEdit/mutations';
import {
  StoreCreateFormValues,
  StoreCreatePayload,
  StoreCreateResponse,
  TerminalFormValues,
} from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';
import { Store, Terminal } from 'merchant/views/StoreSettings/types';

export const convertFormToStoreCreatePayload = (
  formValues: StoreCreateFormValues,
): StoreCreatePayload => {
  return {
    storeType: formValues?.storeType?.trim() || 'ONLINE',
    name: formValues?.storeName?.trim() || '',
    storeCode: formValues?.storeCode?.trim() || '',
    websiteUrl: formValues?.websiteUrl?.trim() || '',
    address: {
      displayAddress: formValues?.displayAddress?.trim() || '',
      line1: formValues?.address?.trim() || '',
      city: formValues?.city?.trim() || '',
      state: formValues?.state?.trim() || '',
      country: formValues?.country?.trim() || '',
      zipcode: formValues?.pinCode?.trim() || '',
    },
    customFields: formValues?.customFields || {},
    pinCode: formValues?.pinCode?.trim() || '',
    primaryContact: {
      number: formValues?.storeContact?.primaryContactNumber?.trim() || '',
    },
    secondaryContact: {
      number: formValues?.storeContact?.secondaryContactNumber?.trim() || '',
    },
    storeInCharge: formValues?.storeContact?.storeInchargeName?.trim() || '',
    brandId: formValues?.digitalBilling?.brandId?.trim() || null,
    linkedProducts: formValues?.linkedProducts || [],
    storeEmail: formValues?.storeContact?.emailId?.trim(),
  };
};

export const convertStoreResponseToForm = (store: Store | null): StoreCreateFormValues => {
  return {
    storeName: store?.name || '',
    websiteUrl: store?.storeInfo?.websiteUrl || '',
    storeCode: store?.storeInfo?.storeCode || '',
    storeType: store?.storeInfo?.storeType || 'ONLINE',
    address: store?.address?.line1 || '',
    pinCode: store?.address?.zipcode || '',
    displayAddress: store?.address?.displayAddress || '',
    customFields: store?.customFields || [],
    storeContact: {
      emailId: store?.storeInfo?.email || '',
      primaryContactNumber: store?.contact?.primary?.number || '',
      secondaryContactNumber: store?.contact?.secondary?.number || '',
      storeInchargeName: store?.storeInfo?.storeInCharge || '',
    },
    city: store?.address?.city || '',
    state: store?.address?.state || '',
    country: store?.address?.country || '',
    digitalBilling: {
      brandId: store?.brand?.id || '',
      brandName: store?.brand?.name || '',
    },
    linkedProducts: store?.storeInfo?.linkedProducts || [],
  };
};

export const convertTerminalResponseToForm = (terminals: Terminal[]): TerminalFormValues => {
  return {
    billingTerminals:
      terminals?.map((terminal) => ({
        id: terminal.id,
        name: terminal.name,
        ipAddress: terminal.terminalInfo.ipAddress,
        macAddress: terminal.terminalInfo.macAddress,
        licenseKey: terminal.terminalInfo.licenseKey,
        isActive: terminal.isActive,
      })) || [],
  };
};

const useStoreCreateMutation = ({ onSuccessHandler, onErrorHandler }) => {
  const { mutate: createStore, isLoading } = useMutation<
    StoreCreateResponse,
    unknown,
    StoreCreatePayload
  >({
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: STORE_CREATE,
        variables,
      }),
    onSuccess: onSuccessHandler,
    onError: onErrorHandler,
  });

  return {
    createStore,
    isLoading,
  };
};

export default useStoreCreateMutation;

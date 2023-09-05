// core
import { useState, useEffect, useMemo, useCallback } from 'react';
///- core

// types
import type {
  StateReturnType,
  CountryReturnType,
  BuyerAddressType,
  BuyerAddressModalProps,
  UseBuyerAddressStateTypes,
} from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/types';
///- types

// apis
import {
  getStatesWithCountryCode,
  getBuyerAddressForPayment,
  saveBuyerAddressForPayment,
} from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/api';
///- apis

// constants
import { COUNTRY_CODES } from 'common/components/CountryCodeInput/constant';
import {
  ALLOWED_COUNTRIES,
  BUYER_ADDRESS_INITIAL_STATE,
} from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/constants';
///- constants

export const useBuyerAddressState = ({
  onClose,
  paymentId,
  showNotification,
}: BuyerAddressModalProps): UseBuyerAddressStateTypes => {
  const countries = useMemo<CountryReturnType>(
    () => COUNTRY_CODES.filter((country) => ALLOWED_COUNTRIES.includes(country.code)),
    [],
  );
  const [states, setStates] = useState<StateReturnType>([]);
  const [isStatesLoading, setStatesLoading] = useState(false);
  const [isAddressLoading, setAddressLoading] = useState(false);
  const [buyerAddress, setBuyerAddress] = useState<BuyerAddressType>(BUYER_ADDRESS_INITIAL_STATE);
  const [isAddressSaving, setAddressSaving] = useState(false);
  const isAddressAlreadyExists = useMemo(() => {
    if (buyerAddress) {
      return Object.keys(buyerAddress).some((key) => buyerAddress[key] !== '');
    }
    return false;
  }, [buyerAddress]);

  const onCountrySelect = (countryCode: string) => {
    if (countryCode) {
      setStatesLoading(true);
      getStatesWithCountryCode(countryCode)
        .then(setStates)
        .finally(() => setStatesLoading(false));
    }
  };

  const onLoadAddress = useCallback(() => {
    setAddressLoading(true);
    getBuyerAddressForPayment(paymentId)
      .then((address) => {
        setBuyerAddress(address || BUYER_ADDRESS_INITIAL_STATE);
      })
      .finally(() => {
        setAddressLoading(false);
      });
  }, [paymentId]);

  const onSaveAddress = useCallback(
    (values: { [x: string]: string }) => {
      setAddressSaving(true);
      saveBuyerAddressForPayment(paymentId, values)
        .then((res) => {
          if (res.success) {
            showNotification({
              type: 'success',
              message: 'Buyer address updated successfully',
            });
          }
        })
        .catch(({ errors }) => {
          showNotification({
            type: 'error',
            message: errors,
          });
        })
        .finally(() => {
          setAddressSaving(false);
          onClose();
        });
    },
    [onClose, paymentId, showNotification],
  );

  useEffect(() => {
    onLoadAddress();
  }, [onLoadAddress]);

  return {
    states,
    countries,
    buyerAddress,
    isAddressSaving,
    isStatesLoading,
    isAddressLoading,
    isAddressAlreadyExists,
    onCountrySelect,
    onSaveAddress,
  };
};

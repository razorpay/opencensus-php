// core
import { useState, useEffect, useMemo, useCallback } from 'react';
///- core

// types
import type {
  StateReturnType,
  CountryReturnType,
  BuyerAddressType,
} from 'merchant/views/Transactions/B2bPayments/components/BuyerAddressModal/types';
///- types

// constants
import { COUNTRY_CODES } from 'common/components/CountryCodeInput/constant';
import {
  ALLOWED_COUNTRIES,
  BUYER_ADDRESS_INITIAL_STATE,
} from 'merchant/views/Transactions/B2bPayments/components/BuyerAddressModal/constants';
///- constants

export const useBuyerAddressState = (_args: {
  paymentId: string;
  onClose: () => void;
  showNotification: (arg: { type: string; message: string }) => void;
}): {
  isStatesLoading: boolean;
  isAddressLoading: boolean;
  isAddressSaving: boolean;
  states: StateReturnType;
  countries: CountryReturnType;
  buyerAddress: BuyerAddressType;
  isAddressAlreadyExists: boolean;
  onCountrySelect: (code: string) => void;
  onSaveAddress: (values: { [x: string]: string }) => void;
} => {
  const countries = useMemo<CountryReturnType>(
    () => COUNTRY_CODES.filter((country) => ALLOWED_COUNTRIES.includes(country.code)),
    [],
  );
  const [states] = useState<StateReturnType>([]);
  const [isStatesLoading, setStatesLoading] = useState(false);
  const [isAddressLoading, setAddressLoading] = useState(false);
  const [buyerAddress] = useState<BuyerAddressType>(BUYER_ADDRESS_INITIAL_STATE);
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
    }
  };

  const onLoadAddress = useCallback(() => {
    setAddressLoading(true);
  }, []);

  const onSaveAddress = useCallback((_values: { [x: string]: string }) => {
    setAddressSaving(true);
  }, []);

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

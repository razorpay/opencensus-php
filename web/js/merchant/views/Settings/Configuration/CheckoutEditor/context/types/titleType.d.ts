import React from 'react';
import {
  MerchantCheckoutStyledConfig,
  AccountConfig,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

export type BrandNameTextInputProps = {
  brandName: string;
  setBrandName: (value: string) => void;
  isDisabled: boolean;
};

export type ChooseTitleTypeProps = {
  setShowTitleTypeModal: React.Dispatch<React.SetStateAction<boolean>>;
  setShowEditModal: React.Dispatch<React.SetStateAction<boolean>>;
  user: any;
  selectedTitleStyle: string;
  setSelectedTitleStyle: React.Dispatch<React.SetStateAction<string>>;
};

export type SingleContentProps = {
  title: string;
  description: string;
  src: string;
  alt: string;
  value: string;
};

export type EditLogoTitleProps = {
  setShowTitleTypeModal: React.Dispatch<React.SetStateAction<boolean>>;
  setShowEditModal: React.Dispatch<React.SetStateAction<boolean>>;
  user: any;
  accountConfig: AccountConfig;
  merchantCheckoutStyledConfig: MerchantCheckoutStyledConfig;
  selectedTitleStyle: string;
  setSelectedTitleStyle: React.Dispatch<React.SetStateAction<string>>;
};

export type BrandColorProps = {
  accountConfig: AccountConfig;
};

export type ImagePreviewProps = {
  file?: File | null;
  src?: string;
};

export type UploadLogoProps = {
  type: 'logo' | 'wordmark';
  image: string;
  imageRaw: null | File;
};

export type ChangeColorInputProps = {
  label?: string;
  value: string;
  onChange: (evt: React.ChangeEvent) => void;
};

export type MessageTextInputProps = {
  message: string;
  placeholder?: string;
  value: string;
  onChange: (event: { value?: string }) => void;
};

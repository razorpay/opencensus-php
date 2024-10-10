import React from 'react';
import { AccountConfig, MerchantCheckoutStyledConfig } from './types';

export type BrandNameTextInputProps = {
  brandName: string;
  setBrandName: (value: string) => void;
};

export type ChooseTitleTypeProps = {
  setShowTitleTypeModal: React.Dispatch<React.SetStateAction<boolean>>;
  setShowEditModal: React.Dispatch<React.SetStateAction<boolean>>;
  user: any;
};

export type SingleContentProps = {
  title: string;
  description: string;
  src: string;
  alt: string;
  value: string;
};

export type EditLogoTitleProps = {
  setShowEditModal: React.Dispatch<React.SetStateAction<boolean>>;
  user: any;
  accountConfig: AccountConfig;
  merchantCheckoutStyledConfig: MerchantCheckoutStyledConfig;
};

export type BrandColorProps = {
  accountConfig: AccountConfig;
};

export type ImagePreviewProps = {
  file?: File | null;
  src?: string;
};

export type UploadLogoProps = {
  logo: string;
  logoRaw: null | File;
  fileName: string;
  setFileName: React.Dispatch<React.SetStateAction<string>>;
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

import React from 'react';

import lazy from 'merchant/routes/LazyLoader';

import MethodSection from './Section';
import { MethodProps } from './types';

const InstantBankTransfer = lazy(
  () =>
    import(
      /* webpackChunkName: "InstantBankTransfer" */ 'merchant/views/Settings/PaymentMethods/components/InstantBankTransfer'
    ),
);

const MethodApp = ({ item }: MethodProps) => {
  if (!item) {
    return null;
  }

  return (
    <MethodSection id={item.slug || 'apps'} header={item.header} description={item.listDescription}>
      <InstantBankTransfer leafList={item} />
    </MethodSection>
  );
};

export default MethodApp;

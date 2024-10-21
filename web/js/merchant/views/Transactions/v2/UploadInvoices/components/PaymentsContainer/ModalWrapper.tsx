import React, { useContext } from 'react';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { OnboardingModalProps } from 'merchant/views/Transactions/v2/UploadInvoices/components/OnboardingModal/types';
import { PartnerLoginProps } from 'merchant/views/Transactions/v2/UploadInvoices/components/PartnerLogin/types';
import { AddInvoiceType } from 'merchant/views/Transactions/v2/UploadInvoices/components/AddInvoice/types';

import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';

const OnboardingModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'OnboardingModal' */ 'merchant/views/Transactions/v2/UploadInvoices/components/OnboardingModal'
    ),
);

const PartnerLogin = lazy(
  () =>
    import(
      /* webpackChunkName: 'PartnerLogin' */ 'merchant/views/Transactions/v2/UploadInvoices/components/PartnerLogin'
    ),
);

const AddInvoiceModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'AddInvoiceModal' */ 'merchant/views/Transactions/v2/UploadInvoices/components/AddInvoice'
    ),
);

const ModalWrapper = () => {
  const {
    popupDetails: { type, props = {} },
  } = useContext(PopupContext);

  return (
    <SuspenseWithLoader>
      {type === MODAL_TYPES.ONBOARDING && <OnboardingModal {...(props as OnboardingModalProps)} />}
      {type === MODAL_TYPES.LOGIN && <PartnerLogin {...(props as PartnerLoginProps)} />}
      {type === MODAL_TYPES.UPLOAD_INVOICE && <AddInvoiceModal {...(props as AddInvoiceType)} />}
    </SuspenseWithLoader>
  );
};

export default ModalWrapper;

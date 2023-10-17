import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import FIRSProvider from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/context';
import { PopupType } from 'merchant/views/AccountAndSettings/InternationalSettings/constants';

const DownloadPopup = lazy(
  () =>
    import(
      /* webpackChunkName: 'FIRSDownloadPopup' */ 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup'
    ),
);

const FIRSTable = lazy(
  () =>
    import(
      /* webpackChunkName: 'FIRSTable' */ 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable'
    ),
);

const ActionPopup = lazy(
  () =>
    import(
      /* webpackChunkName: 'FIRSActionPopup' */ 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/RequestModals/ActionPopup'
    ),
);

const RequestPopup = lazy(
  () =>
    import(
      /* webpackChunkName: 'FIRSRequestPopup' */ 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/RequestModals/RequestPopup'
    ),
);

const FIRS = (): React.ReactElement => {
  return (
    <FIRSProvider>
      <SuspenseWithLoader type="center">
        <FIRSTable />
        <DownloadPopup />
        <RequestPopup />
        <ActionPopup popupType={PopupType.NO_FIRS} />
        <ActionPopup popupType={PopupType.SUCCESS_MODAL} />
      </SuspenseWithLoader>
    </FIRSProvider>
  );
};

export default FIRS;

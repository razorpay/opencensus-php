import React, { useMemo } from 'react';
import { Box, Card, CardBody, Divider } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';

import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import Breadcrumbs from '@apps/digital-bills/src/common/components/Breadcrumbs';
import { DIGITAL_BILLS, ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';
import BillInfo from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillInfo';
import BillReports from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillReports';
import BrandOverviewHeader from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BrandOverviewHeader';
import { Bill } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

type BillDetailsContainerProps = {
  contactNo: string;
  email: string;
  billId: string;
  amount: number;
  transactionType: string;
  invoiceNo: string;
  timestamp: string;
  brandLogo: string;
  brandName: string;
  storeAddress: string;
  visits: Bill['visits'];
  deliveryReport: Bill['deliveryReport'];
  legacyEntityId: string | null;
};

const BillDetailContainer = (props: BillDetailsContainerProps): React.ReactElement => {
  const {
    contactNo,
    email,
    billId,
    amount,
    transactionType,
    invoiceNo = '',
    timestamp,
    brandLogo,
    brandName,
    storeAddress,
    visits,
    deliveryReport,
    legacyEntityId,
  } = props;

  const pageBreadCrumbs: BreadCrumbType[] = useMemo(
    () => [{ label: 'BillMe' }, { label: 'Bills' }, { label: `Bill ID - ${billId}` }],
    [billId],
  );

  const userInfo = {
    contactNo,
    email,
  };

  const billInfo = {
    amount,
    transactionType,
    invoiceNo,
    billId,
    timestamp,
    legacyEntityId,
  };

  return (
    <Card padding="spacing.0" backgroundColor="surface.background.gray.moderate" data-analytics-name="bill-details-section">
      <CardBody>
        <Box marginY="spacing.2" padding="spacing.6">
          <Breadcrumbs items={pageBreadCrumbs} />
          <BrandOverviewHeader brandLogo={brandLogo} brandName={brandName} address={storeAddress} />
        </Box>
        <Divider />
        <ErrorBoundary
          rank={errorService.ErrorRank.P0}
          tags={{ module: DIGITAL_BILLS }}
          fallbackComponent={<ErrorPage description={ERROR_PAGE_DESCRIPTION} />}
        >
          <>
            <BillInfo userInfo={userInfo} billInfo={billInfo} />
            <BillReports visits={visits} deliveryReport={deliveryReport} timestamp={timestamp} />
          </>
        </ErrorBoundary>
      </CardBody>
    </Card>
  );
};

export default BillDetailContainer;

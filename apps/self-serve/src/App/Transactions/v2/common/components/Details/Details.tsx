import React from 'react';
import { ChevronRightIcon, Link, Box } from '@razorpay/blade/components';
import qs from 'query-string';
import { useNavigate } from 'react-router-dom';

import { useMobile } from '@dashboard/shared-ui/hooks';
import ShowWhen from 'shell/components/ShowWhen';
import PaymentDownloadSwiftCopy from 'shell/Transactions/v1/DownloadSwiftCopy';
import { DetailsProps, HandleDetailsClickParams, RouterParams } from './types';
import { paymentMethodOptionsMap } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import {
  mobileBreakoints,
  POS_TRANSACTION_CHANNEL,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

export const handleDetailsClick = ({
  navigate,
  itemId,
  baseUrl,
  initiatePage,
  prevPath,
  isButton,
  rowData,
}: HandleDetailsClickParams & Pick<RouterParams, 'navigate'>): void => {
  const { hash, search } = window.location;
  const { method } = qs.parse(search);
  const paymentMethodSelected =
    (typeof method === 'string' && paymentMethodOptionsMap[method]) || paymentMethodOptionsMap.all;
  let url = `${baseUrl}/${itemId}?init_page=${initiatePage}`;
  if (hash) {
    url += hash;
  }
  if (rowData?.sourceChannel === POS_TRANSACTION_CHANNEL && rowData?.receiverType === 'qr_code') {
    url += '&dashboard_flag=qr_device_detail';
  }

  track({
    objectName: `Transaction Details ${isButton ? 'Button' : 'Row'}`,
    properties: {
      paymentMethodSelected,
      transactionIDActual: itemId,
      section: initiatePage,
    },
  });
  navigate(url, { state: { prevPath } });
};

const Details = ({
  isDisabled,
  itemId,
  baseUrl,
  initiatePage,
  prevPath,
}: DetailsProps): JSX.Element => {
  const navigate = useNavigate();
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  const linkText = isMobile ? '' : 'Details';

  const handleClick = (evt: React.SyntheticEvent<Element, Event>) => {
    evt.stopPropagation();
    handleDetailsClick({
      navigate,
      itemId,
      baseUrl,
      initiatePage,
      prevPath,
      isButton: true,
    });
  };

  return (
    <Box display="flex" gap="spacing.4">
      <ShowWhen additionalCondition={(user: any): boolean => !isMobile && user.isLRSEducationFlow}>
        <PaymentDownloadSwiftCopy isDisabled={isDisabled} paymentId={itemId} asIcon />
      </ShowWhen>
      <Link
        isDisabled={isDisabled}
        variant="button"
        onClick={handleClick}
        icon={ChevronRightIcon}
        iconPosition="right"
      >
        {linkText}
      </Link>
    </Box>
  );
};

export default Details;

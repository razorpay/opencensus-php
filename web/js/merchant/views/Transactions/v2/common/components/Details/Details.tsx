import React from 'react';
import { ChevronRightIcon, Link, Box } from '@razorpay/blade/components';
import qs from 'query-string';
import { useNavigate } from 'react-router-dom';

import { useMobile } from 'common/hooks/useMobile';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { POS_TRANSACTION_CHANNEL } from 'merchant/views/Transactions/constants';
import PaymentDownloadSwiftCopy from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy';
import { paymentMethodOptionsMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import {
  mobileBreakoints,
  TransactionsEntityRoute,
} from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

import { RouterLink } from './styled';
import { DetailsProps, HandleDetailsClickParams, RouterParams } from './types';

const makeUrl = ({ baseUrl, itemId, initiatePage, rowData }: HandleDetailsClickParams): string => {
  const { hash } = window.location;
  let url = `${baseUrl}/${itemId}?init_page=${initiatePage}`;
  if (hash) {
    url += hash;
  }

  if (rowData?.sourceChannel === POS_TRANSACTION_CHANNEL && rowData?.paymentMethod === 'upi') {
    url += '&dashboard_flag=qr_device_detail';
  }

  return url;
};

export const handleDetailsClick = ({
  navigate,
  itemId,
  baseUrl,
  initiatePage,
  isButton,
  rowData,
}: HandleDetailsClickParams & Pick<RouterParams, 'navigate'>): void => {
  const { method } = qs.parse(window.location.search);
  const url = makeUrl({ baseUrl, itemId, initiatePage, rowData });
  const paymentMethodSelected =
    (typeof method === 'string' && paymentMethodOptionsMap[method]) || paymentMethodOptionsMap.all;

  track({
    objectName: `Transaction Details ${isButton ? 'Button' : 'Row'}`,
    properties: {
      paymentMethodSelected,
      transactionIDActual: itemId,
      section: initiatePage,
    },
  });
  navigate(url);
};

const Details = ({ isDisabled, itemId, baseUrl, initiatePage }: DetailsProps): JSX.Element => {
  const navigate = useNavigate();
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  const { abExperiments } = useSplitzService();

  const linkText = isMobile ? '' : 'Details';

  const handleBtnClick = (evt: React.SyntheticEvent<Element, Event>) => {
    evt.stopPropagation();
    handleDetailsClick({
      navigate,
      itemId,
      baseUrl,
      initiatePage,
      isButton: true,
    });
  };

  const handleLinkClick = (evt: React.SyntheticEvent<Element, Event>): void => {
    evt.stopPropagation();
    const { method } = qs.parse(window.location.search);
    const paymentMethodSelected =
      (typeof method === 'string' && paymentMethodOptionsMap[method]) ||
      paymentMethodOptionsMap.all;

    track({
      objectName: `Transaction Details Button`,
      properties: {
        paymentMethodSelected,
        transactionIDActual: itemId,
        section: initiatePage,
      },
    });
  };

  const shouldShowHyperlink =
    baseUrl === TransactionsEntityRoute.PAYMENTS &&
    isExperimentEnabled(abExperiments.toggle_payments_v2_revamp);

  const url = makeUrl({ baseUrl, itemId, initiatePage });

  return (
    <Box display="flex" gap="spacing.4">
      <ShowWhen additionalCondition={(user) => !isMobile && user.isLRSEducationFlow}>
        <PaymentDownloadSwiftCopy isDisabled={isDisabled} paymentId={itemId} asIcon />
      </ShowWhen>

      {shouldShowHyperlink ? (
        <RouterLink onClick={handleLinkClick} to={url}>
          {linkText}
          <ChevronRightIcon color="interactive.icon.primary.normal" />
        </RouterLink>
      ) : (
        <Link
          isDisabled={isDisabled}
          variant="button"
          onClick={handleBtnClick}
          icon={ChevronRightIcon}
          iconPosition="right"
        >
          {linkText}
        </Link>
      )}
    </Box>
  );
};

export default Details;

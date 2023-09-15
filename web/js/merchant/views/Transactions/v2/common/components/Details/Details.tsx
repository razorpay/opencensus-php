import React from 'react';
import { ChevronRightIcon, Link } from '@razorpay/blade/components';
import qs from 'query-string';
import { withRouter } from 'react-router-dom';

import { useMobile } from 'common/hooks/useMobile';
import { paymentMethodOptionsMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

import { DetailsProps, HandleDetailsClickParams } from './types';

export const handleDetailsClick = ({
  history,
  itemId,
  baseUrl,
  initiatePage,
  prevPath,
  isButton,
}: HandleDetailsClickParams): void => {
  const { hash } = window.location;
  const { method } = qs.parse(location.search);
  const paymentMethodSelected =
    (typeof method === 'string' && paymentMethodOptionsMap[method]) || paymentMethodOptionsMap.all;
  let url = `${baseUrl}/${itemId}?init_page=${initiatePage}`;
  if (hash) {
    url += hash;
  }
  track({
    objectName: `Transaction Details ${isButton ? 'Button' : 'Row'}`,
    properties: {
      paymentMethodSelected,
      transactionIDActual: itemId,
      section: initiatePage,
    },
  });
  history.push(url, { prevPath });
};

const Details = ({
  history,
  itemId,
  baseUrl,
  initiatePage,
  prevPath,
}: DetailsProps): JSX.Element => {
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  const linkText = isMobile ? '' : 'Details';
  return (
    <Link
      variant="button"
      onClick={(e) => {
        e.stopPropagation();
        handleDetailsClick({ history, itemId, baseUrl, initiatePage, prevPath, isButton: true });
      }}
      icon={ChevronRightIcon}
      iconPosition="right"
    >
      {linkText}
    </Link>
  );
};

export default withRouter(Details);

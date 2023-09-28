import React from 'react';
import { ChevronRightIcon, Link } from '@razorpay/blade/components';
import qs from 'query-string';
import { useNavigate } from 'react-router-dom';

import { useMobile } from 'common/hooks/useMobile';
import { paymentMethodOptionsMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

import { DetailsProps, HandleDetailsClickParams, RouterParams } from './types';

export const handleDetailsClick = ({
  navigate,
  itemId,
  baseUrl,
  initiatePage,
  prevPath,
  isButton,
}: HandleDetailsClickParams & Pick<RouterParams, 'navigate'>): void => {
  const { hash, search } = window.location;
  const { method } = qs.parse(search);
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
  navigate(url, { state: { prevPath } });
};

const Details = ({ itemId, baseUrl, initiatePage, prevPath }: DetailsProps): JSX.Element => {
  const navigate = useNavigate();
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  const linkText = isMobile ? '' : 'Details';
  return (
    <Link
      variant="button"
      onClick={(e) => {
        e.stopPropagation();
        handleDetailsClick({
          navigate,
          itemId,
          baseUrl,
          initiatePage,
          prevPath,
          isButton: true,
        });
      }}
      icon={ChevronRightIcon}
      iconPosition="right"
    >
      {linkText}
    </Link>
  );
};

export default Details;

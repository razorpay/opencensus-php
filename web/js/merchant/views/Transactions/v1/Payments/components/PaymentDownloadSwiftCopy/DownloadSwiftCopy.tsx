import React, { useState } from 'react';
import { DownloadIcon, Link, Tooltip } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { merchantFetch } from 'merchant/utils/ajax';
import {
  trackDownloadButtonClicked,
  trackDownloadSuccess,
  trackDownloadFailure,
} from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/analytics';
import { PaymentDownloadSwiftCopyProps } from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/types';
import { showNotification } from 'merchant_common/reducers/notifications';

const PaymentDownloadSwiftCopy = ({
  paymentId,
  notify,
  asIcon,
}: PaymentDownloadSwiftCopyProps): JSX.Element => {
  const [isLoading, setIsLoading] = useState(false);

  const handleDownload = async (evt: React.SyntheticEvent<Element, Event>) => {
    evt.stopPropagation();
    trackDownloadButtonClicked();

    setIsLoading(true);
    try {
      const { data } = await merchantFetch({
        url: `merchant/pxb/documents?payment_ids=${paymentId}`,
      });

      const signedUrl = Array.isArray(data?.items) ? data.items[0]?.signed_url : '';

      if (signedUrl) {
        window.open(signedUrl, '_blank');
        trackDownloadSuccess();
        return;
      }
      throw Error('Something went wrong');
    } catch {
      notify({
        type: 'error',
        message: `SWIFT copy is not available for the payment ${paymentId}`,
      });
      trackDownloadFailure();
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Tooltip content="Download SWIFT Copy for Payment">
      <Link variant="button" isDisabled={isLoading} icon={DownloadIcon} onClick={handleDownload}>
        {asIcon ? '' : 'Download SWIFT Copy'}
      </Link>
    </Tooltip>
  );
};

const mapDispatchToProps = {
  notify: showNotification,
};

export default connect(null, mapDispatchToProps)(PaymentDownloadSwiftCopy);

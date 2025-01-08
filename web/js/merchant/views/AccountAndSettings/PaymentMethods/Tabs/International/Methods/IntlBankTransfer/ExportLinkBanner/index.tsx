import React from 'react';
import { Alert, Link, LinkIcon, Text } from '@razorpay/blade/components';

import { useExportLinkBanner } from './states';

const ExportLinkAlert = () => {
  const { text, url, isGenerated, handleCopy } = useExportLinkBanner();

  if (!isGenerated) {
    return null;
  }

  return (
    <Alert
      color="information"
      description={
        <Text as="span">
          Here&apos;s the link with all your account details -{' '}
          <Link href={url} target="_blank" rel="noopener">
            {text}
          </Link>
        </Text>
      }
      icon={LinkIcon}
      isFullWidth
      isDismissible={false}
      marginBottom="spacing.7"
      actions={{
        primary: {
          text: 'Copy link',
          onClick: handleCopy,
        },
      }}
    />
  );
};

export default ExportLinkAlert;

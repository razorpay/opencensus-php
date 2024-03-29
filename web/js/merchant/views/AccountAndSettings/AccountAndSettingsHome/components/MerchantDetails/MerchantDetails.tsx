import { Link, Text } from '@razorpay/blade/components';
import React from 'react';
import { Pointer, StyledMerchantDetails, SubInfo } from './styled';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';

interface MerchantDetailsPropsInterface {
  merchantId: string;
  isMobile?: boolean;
}

const CopyIcon = () => <Pointer className="i i-icon-copy" />;

const MerchantDetails = ({ isMobile, merchantId }: MerchantDetailsPropsInterface): JSX.Element => {
  return (
    <StyledMerchantDetails>
      <SubInfo>
        <Text weight="semibold" color="surface.text.gray.subtle">
          Merchant ID
        </Text>
        <Text color="surface.text.gray.subtle">{merchantId}</Text>
      </SubInfo>
      <CustomClipboard value={merchantId} hideTooltip={isMobile}>
        <Link onClick={() => {}} iconPosition="right" variant="button" icon={CopyIcon}>
          Copy
        </Link>
      </CustomClipboard>
    </StyledMerchantDetails>
  );
};

export default MerchantDetails;

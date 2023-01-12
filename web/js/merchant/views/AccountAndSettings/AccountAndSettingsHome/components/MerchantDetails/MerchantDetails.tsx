import React from 'react';
import { Pointer, SubInfo, StyledMerchantDetails } from './styled';
import { Text, Link } from '@razorpay/blade/components';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { isMobileDevice } from 'merchant/components/Home/data';

interface MerchantDetailsPropsInterface {
  merchantId: string;
  isMobile?: boolean;
}

const CopyIcon = () => <Pointer className="i i-icon-copy" />;

const MerchantDetails = ({ isMobile, merchantId }: MerchantDetailsPropsInterface): JSX.Element => {
  return (
    <StyledMerchantDetails>
      <SubInfo>
        <Text type="subtle" weight="bold">
          Merchant ID
        </Text>
        <Text type="subtle">{merchantId}</Text>
      </SubInfo>
      <CustomClipboard value={merchantId} hideTooltip={isMobile}>
        <Link onClick={() => {}} iconPosition="right" variant="button" icon={CopyIcon}>
          Copy
        </Link>
      </CustomClipboard>
    </StyledMerchantDetails>
  );
};

MerchantDetails.defaultProps = {
  isMobile: isMobileDevice(),
};

export default MerchantDetails;

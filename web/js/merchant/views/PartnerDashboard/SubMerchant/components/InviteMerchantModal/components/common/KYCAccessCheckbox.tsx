import React from 'react';
import { Box, Link, Checkbox, ArrowRightIcon } from '@razorpay/blade/components';

import FAQModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/FAQModal';
import { trackInviteFlowCommonCtaClicked } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';

// eslint-disable-next-line no-duplicate-imports
import type { CheckboxProps } from '@razorpay/blade/components';

type KYCAccessCheckboxProps = Pick<CheckboxProps, 'isChecked' | 'onChange'> & {
  inviteFlow: string;
  productType: string;
};
const KYCAccessCheckbox = ({
  isChecked,
  onChange,
  inviteFlow,
  productType,
}: KYCAccessCheckboxProps): JSX.Element => {
  const KnowMoreCTA = ({ onClick }) => {
    const onLinkClick = () => {
      trackInviteFlowCommonCtaClicked({
        inviteFlow,
        productType,
        ctaClicked: 'Know more about the feature',
      });
      onClick();
    };
    return (
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        padding={['spacing.0', 'spacing.7']}
      >
        <Link size="xsmall" icon={ArrowRightIcon} onClick={onLinkClick} iconPosition="right">
          Know more about the feature
        </Link>
      </Box>
    );
  };
  return (
    <>
      <Checkbox marginTop="spacing.7" isChecked={isChecked} onChange={onChange}>
        I want to assist my client with their KYC
      </Checkbox>
      <FAQModal
        inviteFlow={inviteFlow}
        productType={productType}
        getTriggerComponent={KnowMoreCTA}
      />
    </>
  );
};
export default KYCAccessCheckbox;

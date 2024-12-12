import React from 'react';
import { Button, Link, PlusIcon, Box } from '@razorpay/blade/components';

import { I18ContextStateType } from 'common/i18/types';
import ShowWhen from 'merchant/components/ShowWhen';
import { ADD_NEW_MERCHANT_ELIGIBLE_ROLES } from 'merchant/views/PartnerDashboard/constants';

import useProductActions from './hooks/useProductActions';
import DocsLink from 'merchant/components/DocsLink';

type SideHeaderProps = {
  i18: I18ContextStateType;
  isPlatformPartnerWithPGInviteFlow: boolean;
};
const SideHeader = ({ i18, isPlatformPartnerWithPGInviteFlow }: SideHeaderProps): JSX.Element => {
  const { handleAddMerchant, handleShareReferralLink } = useProductActions();
  return (
    <>
      <ShowWhen
        additionalCondition={
          (currentUser) =>
            currentUser.isPartner() &&
            currentUser.isPartner('reseller', 'aggregator') &&
            !i18.isConfigTagEnabled('partnership.referral_links')
          // TODO v2: enable Share Referral Link for isPlatformPartnerWithPGInviteFlow
        }
      >
        <Box display="inline" paddingRight="spacing.7">
          <DocsLink isTab shouldUseBladeLink />
        </Box>
        <Link onClick={handleShareReferralLink} variant="button">
          Share Referral Link
        </Link>
      </ShowWhen>
      <ShowWhen
        myRole={ADD_NEW_MERCHANT_ELIGIBLE_ROLES}
        additionalCondition={(currentUser) =>
          currentUser.isPartner() &&
          (isPlatformPartnerWithPGInviteFlow || !currentUser.isPartner('pure_platform'))
        }
      >
        <Button
          marginLeft="spacing.7"
          marginTop="spacing.3"
          onClick={handleAddMerchant}
          icon={PlusIcon}
        >
          Add New Clients
        </Button>
      </ShowWhen>
    </>
  );
};
export default SideHeader;

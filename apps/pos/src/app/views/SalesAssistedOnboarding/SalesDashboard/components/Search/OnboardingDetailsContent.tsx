import React from 'react';
import { Text, Box } from '@razorpay/blade/components';
import StatusBadge from 'apps/pos/src/app/components/StatusBadge/StatusBadge';
import { SalesOnboardedMerchant } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import moment from 'moment';

interface OnboardingDetailsContent {
  onboardingDetails: SalesOnboardedMerchant;
}

const detailsItem = (label: string, value: string | undefined) => {
  if (!value) return <></>;

  return (
    <Box
      display={'flex'}
      marginBottom={'spacing.4'}
      justifyContent={'space-between'}
      alignItems={'center'}
      width={'100%'}
    >
      <Box display={'flex'} alignItems={'center'}>
        <Text size="small" color="surface.text.gray.normal">
          {label}
        </Text>
      </Box>
      {label === 'Status' && typeof value === 'string' ? (
        <StatusBadge type={value} size="medium" />
      ) : (
        <Text size="small" color="interactive.text.staticBlack.normal" weight="medium">
          {value}
        </Text>
      )}
    </Box>
  );
};

const OnboardingDetailsContent = ({ onboardingDetails }: OnboardingDetailsContent) => {
  const { status, merchantId, merchantName, merchantMobile, createdAt, email } = onboardingDetails;

  return (
    <Box display={'flex'} flexDirection={'column'} justifyContent={'center'} alignItems={'start'}>
      {detailsItem('Status', status?.toLowerCase())}
      {detailsItem('Merchant Name', merchantName)}
      {detailsItem('Mobile Number', merchantMobile)}
      {detailsItem('Email', email)}
      {detailsItem('MID', merchantId)}
      {detailsItem(
        'MID created on',
        createdAt ? moment.unix(Number(createdAt)).format('ddd, Do MMM’YY') : 'N/A',
      )}
    </Box>
  );
};

export default OnboardingDetailsContent;

import React from 'react';
import {
  Amount,
  ArrowRightIcon,
  Box,
  Heading,
  Text,
  Link,
  Badge,
} from '@razorpay/blade/components';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { useMobile } from 'common/hooks/useMobile';
import { getFormattedDateFromTimestamp } from './utils';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import { NavLink } from 'react-router-dom';
import { ROUTES } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';

const SettlementInfoBar: React.FC<any> = ({ data }) => {
  const isMobile = useMobile(mobileBreakoints);
  const settlementData = data?.hero_card_data?.settlement;
  const prev = settlementData?.previous?.total_amount;
  const current = settlementData?.current_balance || 0;
  const current_currency = settlementData?.current_balance_currency || 'INR';
  const createdAtTimestamp = settlementData?.previous?.created_at;

  const lastDepositDate = getFormattedDateFromTimestamp(createdAtTimestamp);
  const prev_amount = i18nifyConvertToMajorUnit(prev || 0, current_currency);
  const current_amount = i18nifyConvertToMajorUnit(current, current_currency);

  return (
    <Box
      display="flex"
      justifyContent={{ base: 'space-between', sm: 'none' }}
      alignItems={isMobile ? 'flex-start' : 'center'}
      flexDirection={{ base: 'column', l: 'row' }}
      gap={isMobile ? 'spacing.0' : 'spacing.10'}
    >
      <Box display="flex" gap={isMobile ? 'spacing.4' : 'spacing.5'} alignItems="center">
        <Heading size={isMobile ? 'medium' : 'large'}>Current Balance</Heading>
        <Amount
          value={i18nifyConvertToMajorUnit(current, current_currency)}
          size={isMobile ? 'medium' : 'large'}
          type="heading"
          weight="semibold"
        />
      </Box>

      {isMobile ? (
        <Box
          display="flex"
          alignItems="center"
          gap="spacing.3"
          justifyContent="flex-start"
          marginBottom="spacing.3"
        >
          <Text>Last Deposit:</Text>
          <Amount value={current_amount} size="medium" weight="semibold" />
          <Badge color="positive" size="medium">
            Processed
          </Badge>
        </Box>
      ) : (
        <Box display="flex" gap="spacing.3" alignItems="center">
          <Amount value={prev_amount} size="large" type="heading" />
          <Text size="large" color="surface.text.gray.subtle">
            last deposited on {lastDepositDate}
          </Text>
          <Badge color="positive" size="large">
            Processed
          </Badge>
        </Box>
      )}

      <NavLink to={ROUTES.SETTLEMENT}>
        <Link icon={ArrowRightIcon} iconPosition="right" variant="button" size="medium">
          View all settlements
        </Link>
      </NavLink>
    </Box>
  );
};

export default SettlementInfoBar;

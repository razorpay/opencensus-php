import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import { useQuery } from 'react-query';

import Spinner from 'common/ui/Spinner';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import { Amount, Box, Heading as BaseHeading, Text as BaseText } from '@razorpay/blade/components';
import LimitUtilisationGraph from 'merchant/views/Wallet/AccountDetail/components/BalanceUtilisationGraph';

import { fetchAccountBalance } from 'merchant/views/Wallet/queries';

import { BREAKDOWN } from 'merchant/views/Wallet/AccountDetail/constants';

import type { AccountBalance } from 'merchant/views/Wallet/types';
import type { ModeT } from 'common/services/mode';

const Text = ({ children }) => (
  <BaseText size="small" weight="regular" variant="body" color="surface.text.muted.lowContrast">
    {children}
  </BaseText>
);

const Divider = styled.div(
  ({ theme }) => `
  border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.normal.lowContrast};
  margin: ${theme.spacing[4]}px 0;
  padding: 0 ${theme.spacing[6]}px;
`,
);

const Card = styled.div(
  ({ theme }) => `
margin-top: ${theme.spacing[3]}px;
padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;
background: ${theme.colors.surface.background.level2.lowContrast};
border: ${theme.border.width.thick}px solid ${theme.colors.surface.border.normal.lowContrast};
border-radius: ${theme.border.radius.small}px;
`,
);

export interface AccountBalanceContainerProps {
  account_id: string;
  mode: ModeT;
}

export interface UsageInterface {
  limitUsed: number;
  maxLimit: number;
  limitUnused: number;
}

const AccountBalanceContainer = ({
  account_id,
  mode,
}: AccountBalanceContainerProps): JSX.Element => {
  const [activeBreakdown, setActiveBreakdown] = useState(BREAKDOWN.MONTHLY.value);

  const { data, isLoading } = useQuery<AccountBalance>({
    queryKey: ['wallet:accounts:balance', mode, account_id],
    queryFn: () => fetchAccountBalance({ id: account_id, mode }),
  });

  const [usage, setUsage] = useState<UsageInterface>({
    limitUsed: 0,
    maxLimit: 0,
    limitUnused: 0,
  });

  useEffect(() => {
    setUsage({
      maxLimit: data?.limits?.[`${activeBreakdown}_load_limit`] || 0,
      limitUnused: data?.limits?.[`${activeBreakdown}_load_limit_balance`] || 0,
      limitUsed: data?.limits?.[`${activeBreakdown}_load_limit_used`] || 0,
    });
  }, [activeBreakdown, data]);

  return (
    <Box
      backgroundColor="surface.background.level1.lowContrast"
      padding={['spacing.6', 'spacing.7']}
    >
      <BaseHeading size="small">Account Utilisation</BaseHeading>

      {isLoading ? (
        <Spinner center="center" />
      ) : (
        <Card>
          <Box display="flex" gap="spacing.7" alignItems="center">
            <Box display="inline-flex" flexDirection="column">
              <Amount
                testID="amount"
                size="body-small-bold"
                value={(data?.available_balance || 0) / 100}
                currency="INR"
                isAffixSubtle={false}
              />
              <Text>Available Balance</Text>
            </Box>
          </Box>

          <Divider />

          <div className="panel-actions">
            <BtnGroup
              value={activeBreakdown}
              onChange={setActiveBreakdown}
              className="panel-action-item time-breakdown"
            >
              <Btn value={BREAKDOWN.MONTHLY.value} className="btn-default">
                <span>{BREAKDOWN.MONTHLY.label}</span>
              </Btn>
              <Btn value={BREAKDOWN.YEARLY.value} className="btn-default">
                <span>{BREAKDOWN.YEARLY.label}</span>
              </Btn>
            </BtnGroup>
          </div>
          <LimitUtilisationGraph {...usage} />
        </Card>
      )}
    </Box>
  );
};

export default AccountBalanceContainer;

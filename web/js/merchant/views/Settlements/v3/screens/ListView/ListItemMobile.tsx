import React from 'react';
import { Box, ChevronRightIcon, Link, Text } from '@razorpay/blade/components';
import { SettlementInfo, User } from 'common/typings';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import StatusBadge from 'merchant/views/Settlements/v3/components/StatusBadge';
import { StyledSettlementRow } from 'merchant/views/Settlements/v3/screens/ListView/styled';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

type Props = RouteComponentProps & {
  settlement: SettlementInfo;
  initiatePage: string;
  user: User;
};

const SettlementListItemMobile = ({
  settlement,
  initiatePage,
  user,
  history,
}: Props): JSX.Element => {
  const currency = user.merchant?.currency;

  const instrumentItemClick = () => {
    analyticsTrackWithUserInfo({
      objectName: 'Settlement Details',
      actionName: 'clicked',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
  };

  return (
    <StyledSettlementRow>
      <td>
        <Box
          as="span"
          display="flex"
          columnGap="spacing.3"
          alignItems="flex-start"
          marginBottom="spacing.2"
        >
          <Text type="subtle">
            <Time value={settlement.created_at} format="MMM DD, YYYY" />
          </Text>
          <StatusBadge status={settlement.status} />
        </Box>
        <Text type="subtle" size="small">
          {settlement.id}
        </Text>
      </td>

      <td className="text-right">
        <Box as="span" display="flex" justifyContent="end" columnGap="spacing.3">
          <Box as="span" display="flex" alignItems="center" justifyContent="flex-end">
            <Amount value={settlement.amount} currency={currency} />
          </Box>
          <Link
            variant="button"
            onClick={() => {
              instrumentItemClick();
              history.push(
                `/settlements/${settlement.id}?init_point=settlements-table&init_page=${initiatePage}`,
              );
            }}
            icon={ChevronRightIcon}
            iconPosition="right"
          />
        </Box>
      </td>
    </StyledSettlementRow>
  );
};

export default withRouter<Props>(SettlementListItemMobile);

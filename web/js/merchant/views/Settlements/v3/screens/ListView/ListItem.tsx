import React, { useState } from 'react';
import { Box, ChevronRightIcon, Heading, InfoIcon, Link, Text } from '@razorpay/blade/components';
import { SettlementInfo, User, ShowNotificationType } from 'common/typings';
import Amount from 'common/ui/Amount';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Time from 'common/ui/Time';
import StatusBadge from 'merchant/views/Settlements/v3/components/StatusBadge';
import CopyButton from 'merchant/views/Settlements/v3/screens/ListView/CopyButton';
import {
  StyledDivider,
  StyledSettlementRow,
} from 'merchant/views/Settlements/v3/screens/ListView/styled';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { fetchBreakupDetails, isBreakupNew } from 'merchant/reducers/settlements/details';
import Shimmer from 'common/components/Shimmer';
import { getBreakUpDetails } from 'merchant/views/Settlements/v3/components/Breakup/config';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { showPaymentProviderColumn } from 'merchant/views/Settlements/v3/utils/common';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

type Props = RouteComponentProps & {
  settlement: SettlementInfo;
  initiatePage: string;
  user: User;
  terminalProviders: unknown;
};

type ReduxProps = {
  showNotification: ShowNotificationType;
};

type BreakupInfo = {
  grossSettlement: number;
  netSettlement: number;
  deductions: number;
};

const SettlementListItem = ({
  settlement,
  initiatePage,
  user,
  terminalProviders,
  history,
  showNotification,
}: Props & ReduxProps): JSX.Element => {
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

  const [breakupInfo, setBreakupInfo] = useState<BreakupInfo | null>(null);

  const instrumentBreakupView = () => {
    analyticsTrackWithUserInfo({
      objectName: 'Break Up',
      actionName: 'Clicked',
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

  const onTooltipVisibilityUpdate = async (visible) => {
    // only make the api call when breakup info is not loaded
    if (visible && !breakupInfo) {
      try {
        const { success, data, error } = await fetchBreakupDetails({ id: settlement.id }).payload;
        if (success && !!data.items.length) {
          const isSettlementBreakupNew = isBreakupNew(data.items[0]);
          const { grossSettlements, deductions, netSettlements } = getBreakUpDetails({
            items: data.items,
            isBreakupNew: isSettlementBreakupNew,
          });
          setBreakupInfo({
            grossSettlement: grossSettlements.amount,
            netSettlement: netSettlements.amount,
            deductions: deductions.amount,
          });
          instrumentBreakupView();
        } else {
          showNotification({
            type: 'error',
            message: error || "Couldn't load breakup",
          });
        }
      } catch {
        showNotification({
          type: 'error',
          message: "Couldn't load breakup",
        });
      }
    }
  };

  return (
    <StyledSettlementRow>
      <td>
        <Text type="subtle">
          <Time value={settlement.created_at} format="MMM DD YYYY, hh:mma" />
        </Text>
      </td>

      <td>
        <CopyButton text={settlement.id} type="settlement-id" settlement={settlement} />
      </td>
      {showPaymentProviderColumn(user) && (
        <td>
          <PaymentOptimizerProvider
            terminal_id={settlement?.optimizer_provider}
            settled_by={settlement?.settled_by}
            terminalProviders={terminalProviders}
            hideExternalLink={true}
          />
        </td>
      )}
      <td>
        <CopyButton
          text={settlement.utr ?? '-'}
          disabled={!settlement.utr}
          type="settlement-utr"
          settlement={settlement}
        />
      </td>
      <td className="text-right">
        <Box as="span" display="flex" alignItems="center" justifyContent="flex-end">
          <Amount value={settlement.amount} currency={currency} />
          <Box as="span" display="flex">
            <InfoIcon
              marginLeft="spacing.2"
              size="medium"
              color="surface.text.normal.lowContrast"
            />
            <PopoverComponent
              className="settlement-breakup-tooltip"
              align="right"
              theme="light"
              parentQuerySelector="content-wrapper"
              onVisibilityChange={onTooltipVisibilityUpdate}
            >
              <PopoverBody>
                <Box
                  height="spacing.9"
                  display="flex"
                  backgroundColor="surface.background.level3.lowContrast"
                  alignItems="center"
                  paddingLeft="spacing.6"
                >
                  <Heading variant="subheading">Settlement breakup for&nbsp;</Heading>
                  <Text type="subtle" size="small">
                    {settlement.id}
                  </Text>
                </Box>
                <Box paddingTop="spacing.4" paddingBottom="spacing.5">
                  <Box
                    display="flex"
                    alignItems="center"
                    justifyContent="space-between"
                    paddingX="spacing.6"
                    marginBottom="spacing.4"
                  >
                    {breakupInfo ? (
                      <>
                        <Text size="small" type="subtle">
                          Gross settlement
                        </Text>
                        <Text size="small" type="subtle">
                          <Amount value={breakupInfo.grossSettlement} currency={currency} />
                        </Text>
                      </>
                    ) : (
                      <>
                        <Shimmer height="16px" width="90px" />
                        <Shimmer height="16px" width="60px" />
                      </>
                    )}
                  </Box>
                  <Box
                    display="flex"
                    alignItems="center"
                    justifyContent="space-between"
                    paddingX="spacing.6"
                    marginBottom="spacing.3"
                  >
                    {breakupInfo ? (
                      <>
                        <Text size="small" type="subtle">
                          Deductions
                        </Text>
                        <Text size="small" color="feedback.text.negative.lowContrast">
                          - <Amount value={breakupInfo.deductions} currency={currency} />
                        </Text>
                      </>
                    ) : (
                      <>
                        <Shimmer height="16px" width="90px" />
                        <Shimmer height="16px" width="40px" />
                      </>
                    )}
                  </Box>
                  <StyledDivider />
                  <Box
                    display="flex"
                    alignItems="center"
                    justifyContent="space-between"
                    paddingX="spacing.6"
                    paddingTop="spacing.3"
                  >
                    {breakupInfo ? (
                      <>
                        <Text size="small" type="subtle" weight="bold">
                          Net Settlement
                        </Text>
                        <Text size="small" type="subtle" weight="bold">
                          <Amount value={breakupInfo.netSettlement} currency={currency} />
                        </Text>
                      </>
                    ) : (
                      <>
                        <Shimmer height="16px" width="80px" />
                        <Shimmer height="16px" width="60px" />
                      </>
                    )}
                  </Box>
                </Box>
              </PopoverBody>
            </PopoverComponent>
          </Box>
        </Box>
      </td>
      <td>
        <StatusBadge status={settlement.status} />
      </td>
      <td>
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
        >
          Details
        </Link>
      </td>
    </StyledSettlementRow>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification: showNotificationFn,
    },
    dispatch,
  );

export default withRouter<Props>(connect(null, mapDispatchToProps)(SettlementListItem));

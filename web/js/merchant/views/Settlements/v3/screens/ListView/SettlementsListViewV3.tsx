import { Box, InfoIcon, Spinner, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { SettlementsCollectionReducerState, User } from 'common/typings';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import TableBody from 'common/ui/TableBody';
import React from 'react';
import { settlementListViewHeaders, settlementListViewMobileHeaders } from './constants';
import SettlementListItem from './ListItem';
import SettlementListItemMobile from './ListItemMobile';
import NoSettlement from './NoSettlements';
import {
  StyledLoaderCell,
  StyledSettlementListTable,
  StyledSettlementListTableHeaderCell,
} from './styled';

type Props = SettlementsCollectionReducerState & {
  user: User;
  terminalProviders: any;
  selfServeActionsPage: string;
  settlements: SettlementsCollectionReducerState['items'];
  isLoading: boolean;
};

const SettlementsListViewV3 = ({
  user,
  settlements,
  isLoading,
  terminalProviders,
  selfServeActionsPage = 'Settlements.Settlements',
}: Props): JSX.Element => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  const isMobile = matchedDeviceType === 'mobile';
  const headersToShow = isMobile ? settlementListViewMobileHeaders : settlementListViewHeaders;
  const SettlementListItemComponent = isMobile ? SettlementListItemMobile : SettlementListItem;
  const colSpan = isMobile ? 2 : 7;

  return (
    <Box overflowX="auto">
      <StyledSettlementListTable>
        <thead>
          <tr>
            {headersToShow.map((header, idx) => {
              const shouldShowHeader = header.condition ? header.condition(user) : true;

              return (
                <React.Fragment key={idx + header.title}>
                  {shouldShowHeader ? (
                    <StyledSettlementListTableHeaderCell hasToolTip={!!header.tooltip}>
                      <Text weight="semibold" color="surface.text.gray.subtle">
                        {header.title}
                        {header.tooltip && (
                          <>
                            <InfoIcon
                              marginLeft="spacing.2"
                              size="medium"
                              color="interactive.icon.gray.normal"
                            />
                            <PopoverComponent
                              align="right"
                              theme="dark"
                              parentQuerySelector="content-wrapper"
                            >
                              <PopoverBody>{header.tooltip}</PopoverBody>
                            </PopoverComponent>
                          </>
                        )}
                      </Text>
                    </StyledSettlementListTableHeaderCell>
                  ) : null}
                </React.Fragment>
              );
            })}
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          rows={settlements}
          emptyTableMsg="No Settlements found!"
          emptyTableRow={(colSpan) => <NoSettlement colSpan={colSpan} />}
          colSpan={colSpan}
          SpinnerComponent={() => (
            <tr>
              <StyledLoaderCell colSpan={colSpan}>
                <Spinner size="large" accessibilityLabel="" />
              </StyledLoaderCell>
            </tr>
          )}
        >
          {settlements.map((settlement) => (
            <SettlementListItemComponent
              key={settlement.id}
              settlement={settlement}
              user={user}
              terminalProviders={terminalProviders}
              initiatePage={selfServeActionsPage}
            />
          ))}
        </TableBody>
      </StyledSettlementListTable>
    </Box>
  );
};

export default SettlementsListViewV3;

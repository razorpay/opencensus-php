import { Button, ChevronDownIcon, EditIcon, Heading, Text } from '@razorpay/blade/components';
import Collapsible from 'common/components/Collapsible';
import BankDetails from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BankDetails';
import {
  AccountSectionPropsInterface,
  FLOW_TYPE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React, { useState, useMemo } from 'react';
import { connect } from 'react-redux';
import {
  CollapsibleIcon,
  HeaderTopBar,
  StyledAccountSectionContainer,
  StyledAccountSectionContent,
  StyledAccountSectionHeader,
} from './styled';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { Modules } from 'common/constant/enums';

const AccountSection = ({
  user,
  switchAction,
  isCtaAction,
  isCollapsible,
  accountData: { title, description, banks = [] },
  isMobile,
  handleAction,
}: AccountSectionPropsInterface): JSX.Element => {
  const [isShow, setIsShow] = useState(!isCollapsible);

  const handleToggle = (): void => setIsShow((prevState) => !prevState);

  const handleChangeBankAction = (): void => {
    selfServeTrackInitiate({
      selfServeAction: 'Bank Account Updated',
      page: 'Profile',
      screen: Modules.AccountAndSettings,
      props: {
        version: 'v2',
      },
    });
    trackBankAccountUpdateEvent({
      objectName: 'Bank Account Update Edit',
      actionName: 'Clicked',
    });
    handleAction({ flowType: FLOW_TYPE.UPDATE });
  };

  const actionCta = banks.length ? 'Change bank account' : 'Add bank account';

  const isImportMerchant = useMemo(() => {
    return Array.isArray(user.tags)
      ? user.tags.some((tag) =>
          ['opgsp_import_flow', 'enable_jpmc_import_flow'].includes(tag.toLowerCase()),
        )
      : false;
  }, [user.tags]);

  /**
   * Bank account update button should not be visible for import merchants
   * with feature flag opgsp_import_flow, enable_jpmc_import_flow
   */
  const isBankAccountUpdateAllowed = banks.length ? !isImportMerchant : true;

  return (
    <StyledAccountSectionContainer>
      <StyledAccountSectionHeader>
        <HeaderTopBar>
          <Heading size="small">{title}</Heading>
          {isCollapsible ? (
            <CollapsibleIcon onClick={handleToggle} open={isShow} data-testid="collapse-btn">
              <ChevronDownIcon size="large" color="interactive.icon.primary.subtle" />
            </CollapsibleIcon>
          ) : !isMobile &&
            isCtaAction &&
            isBankAccountUpdateAllowed &&
            (user.isCountryIndia) ? (
            <Button
              variant="primary"
              icon={EditIcon}
              iconPosition="left"
              onClick={handleChangeBankAction}
            >
              {actionCta}
            </Button>
          ) : null}
        </HeaderTopBar>
        <Text color="surface.text.gray.subtle">{description}</Text>
        {isMobile &&
          isCtaAction &&
          isBankAccountUpdateAllowed &&
          (user.isCountryIndia) && (
            <Button
              variant="primary"
              icon={EditIcon}
              iconPosition="left"
              onClick={handleChangeBankAction}
            >
              {actionCta}
            </Button>
          )}
      </StyledAccountSectionHeader>
      <Collapsible open={isShow}>
        <StyledAccountSectionContent isSections={!!banks.length}>
          {banks.length ? (
            banks.map(
              (each, index): JSX.Element => (
                <BankDetails
                  key={index}
                  bankData={each}
                  handleAction={handleAction}
                  {...(switchAction ? { switchAction } : {})}
                />
              ),
            )
          ) : (
            <Text color="surface.text.gray.subtle">NO BANK ACCOUNT AVAILABLE</Text>
          )}
        </StyledAccountSectionContent>
      </Collapsible>
    </StyledAccountSectionContainer>
  );
};

const mapStateToProps = (state) => ({
  isMobile: state.app.isMobileResolution,
  user: state.session.user,
});

export default connect(mapStateToProps, null)(AccountSection);

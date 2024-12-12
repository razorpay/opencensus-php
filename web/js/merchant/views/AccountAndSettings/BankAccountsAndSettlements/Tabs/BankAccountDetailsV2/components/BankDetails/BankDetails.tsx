import { BankIcon, Link, StampIcon, Text } from '@razorpay/blade/components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { truncatedString } from 'common/utils/rzp-utils';
import {
  BankDataInterface,
  BankDetailsPropsInterface,
  FIELD_TYPE,
  FLOW_TYPE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';
import {
  AccountDetails,
  BankAccountIconSection,
  Chip,
  ChipContainer,
  StyledBankDetailsContainer,
  StyledLink,
} from './styled';

const RenderTypeValue = ({ id, type, value }: Omit<BankDataInterface, 'name'>): JSX.Element => {
  switch (type) {
    case FIELD_TYPE.CHIP:
      return (
        <ChipContainer>
          <Chip value={value}>
            <i className="i i-info-tooltip" />
            <span>{value}</span>
          </Chip>
          <Popover theme="dark" align="top">
            <PopoverBody>
              <div>
                Your payments are currently {value === 'ACTIVE' ? '' : 'not '}being deposited in
                this bank account
              </div>
            </PopoverBody>
          </Popover>
        </ChipContainer>
      );
    default:
      return (
        <Text color="surface.text.gray.subtle">
          {id === 'name' ? truncatedString(value, 23) : value}
        </Text>
      );
  }
};

const BankDetails = ({
  bankData,
  switchAction,
  handleAction,
}: BankDetailsPropsInterface): JSX.Element => {
  const handleSwitchAction = (): void =>
    handleAction({ bankDetails: bankData, flowType: FLOW_TYPE.SWITCH });
  const settlementsValue = bankData?.find((detail) => detail.id === 'settlements')?.value;
  return (
    <StyledBankDetailsContainer>
      <BankAccountIconSection>
        {settlementsValue === 'ACTIVE' ? (
          <BankIcon color="interactive.icon.gray.normal" size="large" />
        ) : (
          <StampIcon color="interactive.icon.gray.normal" size="large" />
        )}
      </BankAccountIconSection>
      {bankData?.map(
        ({ id, name, value, type }): JSX.Element => (
          <AccountDetails key={id}>
            <Text weight="semibold" color="surface.text.gray.subtle">
              {name}
            </Text>
            <RenderTypeValue id={id} type={type} value={value} />
          </AccountDetails>
        ),
      )}
      {switchAction && (
        <StyledLink isDisable={switchAction.isDisable}>
          <Link variant="button" onClick={handleSwitchAction} isDisabled={switchAction.isDisable}>
            {switchAction.title}
          </Link>
        </StyledLink>
      )}
    </StyledBankDetailsContainer>
  );
};

export default BankDetails;

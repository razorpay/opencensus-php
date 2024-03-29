import { Box, CopyIcon, Text } from '@razorpay/blade/components';
import { User } from 'common/typings';
import { getSettlementDate } from 'merchant/views/Settlements/v3/utils/common';
import { getSettlementInfo } from 'merchant/views/Settlements/v3/utils/settlementInfo';
import React from 'react';
import { connect } from 'react-redux';
import { InfoItem, StyledInfoValue } from './styled';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Amount from 'merchant/views/Settlements/v3/components/Amount';
import StatusBadge from 'merchant/views/Settlements/v3/components/StatusBadge/StatusBadge';
import {
  SettlementInfoInterface,
  SettlementPropsInterface,
  VALUE_TYPE,
} from 'merchant/views/Settlements/v3/typings';

const InfoItemValue = ({
  value,
  type,
  user,
}: Pick<SettlementInfoInterface, 'value' | 'type'> & { user: Required<User> }): JSX.Element => {
  switch (type) {
    case VALUE_TYPE.AMOUNT: {
      if (value == null) return <span>---</span>;
      const {
        merchant: { currency },
      } = user;
      return <Amount amount={value} currency={currency} />;
    }
    case VALUE_TYPE.DATE: {
      const { date, time } = getSettlementDate(value);
      return (
        <StyledInfoValue gap="5px">
          <Text weight="regular" size="large">
            {date}
          </Text>
          <Text size="medium" color="surface.text.gray.muted">
            {time}
          </Text>
        </StyledInfoValue>
      );
    }
    case VALUE_TYPE.CHIP:
      return value ? <StatusBadge status={value} /> : <span>---</span>;
    default:
      return (
        <Text weight="regular" size="large">
          {value || '---'}
        </Text>
      );
  }
};

const SettlementInfo = ({
  settlement,
  user,
}: {
  settlement: SettlementPropsInterface;
  user: Required<User>;
}): JSX.Element => {
  const settlementData = getSettlementInfo({ settlement });

  const onItemCopy = (item) => {
    if (item.onItemCopy)
      item.onItemCopy({
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        utrNumber: item.value,
        sessionId: window?.session_id ? window.session_id : undefined,
      });
  };

  return (
    <Box
      display="flex"
      flexWrap="wrap"
      backgroundColor="surface.background.gray.intense"
      flexDirection={{ base: 'column', m: 'row' }}
      padding={{ base: 'spacing.7', m: ['spacing.8', 'spacing.0'] }}
      rowGap={{ base: 'spacing.7', m: 'spacing.6' }}
    >
      {settlementData.map((each, index) => (
        <InfoItem key={each.id} isBorder={index < settlementData.length - 1}>
          <Text size="medium" color="surface.text.gray.subtle">
            {each.name}
          </Text>
          {each.isCopy && each.value ? (
            <CustomClipboard value={each.value} onCopy={onItemCopy.bind(null, each)}>
              <StyledInfoValue gap="5px">
                <InfoItemValue {...each} user={user} />
                <CopyIcon size="medium" color="feedback.icon.neutral.intense" />
              </StyledInfoValue>
            </CustomClipboard>
          ) : (
            <InfoItemValue {...each} user={user} />
          )}
        </InfoItem>
      ))}
    </Box>
  );
};

const mapStateToProps = (state) => {
  const { settlement, session } = state;
  return {
    settlement: settlement.settlement,
    user: session.user,
  };
};

export default connect(mapStateToProps, null)(SettlementInfo);

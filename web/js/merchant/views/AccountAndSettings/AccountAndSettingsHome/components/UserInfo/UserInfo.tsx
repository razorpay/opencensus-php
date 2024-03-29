import React from 'react';
import {
  UserInfoContainer,
  UserInfoItem,
  Pointer,
  SubInfo,
  IconText,
  TooltipContainer,
} from './styled';
import { Text } from '@razorpay/blade/components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { UserInfoPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { truncatedString } from 'common/utils/rzp-utils';

const UserInfo = ({ infoData, onClick, isMobile }: UserInfoPropsInterface): JSX.Element => {
  return (
    <UserInfoContainer>
      {infoData.map((each, index) => {
        return (
          <UserInfoItem key={`${each.id}_${index}`}>
            <IconText>
              <Text weight="semibold" color="surface.text.gray.subtle">
                {each.displayName}
              </Text>
              {each.tooltip && (
                <TooltipContainer className="name-tooltip">
                  <i className="i i-info-tooltip" />
                  <Popover theme="dark" align="top" horizontalAdjustment={isMobile ? 80 : 100}>
                    <PopoverBody>
                      <div>{each.tooltip?.description}</div>
                    </PopoverBody>
                  </Popover>
                </TooltipContainer>
              )}
            </IconText>
            <SubInfo>
              <Text color="surface.text.gray.subtle">{truncatedString(each.value, 26)}</Text>
              {each.isEditEnable ? (
                <Pointer className="i i-icon-container" onClick={() => onClick(each)} />
              ) : null}
            </SubInfo>
          </UserInfoItem>
        );
      })}
    </UserInfoContainer>
  );
};

export default UserInfo;

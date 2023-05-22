import { Box, Link, Text } from '@razorpay/blade/components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Verification from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/Verification';
import { UserInfoPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';
import { StyledInfo, TooltipContainer } from './styled';

const UserInfo = ({ isMobile, infoData, onClick }: UserInfoPropsInterface): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap={{ base: 'spacing.6', m: '10px' }}
      padding={{ base: 'spacing.0', m: 'spacing.7' }}
    >
      {infoData.map(
        (each, index): JSX.Element => (
          <Box
            key={`${each.id}_${index}`}
            display="flex"
            alignItems={{ base: 'initial', m: 'center' }}
            flexDirection={{ base: 'column', m: 'row' }}
          >
            {isMobile ? (
              <Text weight="bold">{each.displayName}</Text>
            ) : (
              <Box minWidth="160px">
                <Text weight="bold">{each.displayName}</Text>
              </Box>
            )}
            <StyledInfo>
              <Text type="subdued">{each.value}</Text>
              <TooltipContainer isTooltipAction={!!(!each.isEditEnable && each.editTooltip)}>
                <Link
                  variant="button"
                  size="small"
                  onClick={onClick.bind(null, each)}
                  isDisabled={!each.isEditEnable}
                >
                  Edit
                </Link>
                {!each.isEditEnable && each.editTooltip && (
                  <Popover theme="dark" align="top">
                    <PopoverBody>
                      <div>{each.editTooltip?.description}</div>
                    </PopoverBody>
                  </Popover>
                )}
              </TooltipContainer>
            </StyledInfo>
          </Box>
        ),
      )}
      <Verification isMobile={isMobile} />
    </Box>
  );
};

export default UserInfo;

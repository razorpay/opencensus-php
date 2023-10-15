import { Box, Link, Text } from '@razorpay/blade/components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Verification from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/Verification';
import {
  UserInfoPropsInterface,
  PersonalProfileFields,
  ActiveModalI,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';
import { StyledInfo, TooltipContainer } from './styled';
import ModalForm from 'merchant/views/AccountAndSettings/common/components/ModalForm';
import { updateDisplayNameHandler } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/handlers';
import { updateMerchantConfig } from 'merchant/reducers/profile';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateSession } from 'merchant/reducers/session';

const UserInfo = ({
  isMobile,
  infoData,
  onClick,
  updateMerchantConfig,
  updateSession,
  showNotification,
  user,
}: UserInfoPropsInterface): JSX.Element => {
  const [activeModal, setActiveModal] = React.useState<ActiveModalI | null>(null);

  const onModalClose = () => {
    setActiveModal(null);
  };

  const onUpdateClick = (userInput): void => {
    const id = activeModal!.id;
    if (id === PersonalProfileFields.DISPLAY_NAME) {
      const props = { updateMerchantConfig, updateSession, showNotification, user };
      updateDisplayNameHandler(props)({ display_name: userInput }, () => {
        setActiveModal(null);
      });
    }
  };

  const onEditClick = (item) => {
    if (item.id === PersonalProfileFields.DISPLAY_NAME) {
      setActiveModal(item);
    } else {
      onClick(item);
    }
  };

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
                  onClick={() => onEditClick(each)}
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
      <ModalForm
        showModal={!!activeModal}
        onModalDismiss={onModalClose}
        onUpdateClick={onUpdateClick}
        entity={activeModal}
      />
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateMerchantConfig,
      updateSession,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(UserInfo);

import React from 'react';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import ShowWhen from 'merchant/components/ShowWhen';
import { bindActionCreators } from 'redux';
import {
  GSTIN_UPDATE,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Account/Profile/deeplink-constants';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { Button, Box, StampIcon, Text } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { HeaderProps } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/types';
import { track } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/tracking';
import { useValidatePermissions, ValidatePermissions } from 'merchant/helpers/permissions/utils';
import { PERMISSIONS } from 'merchant/helpers/permissions/constant';

const UpdateModal = lazy(() => import(/* webpackChunkName: 'UpdateModal' */ './UpdateModal'));

const Header = ({ openModal, gstList, defaultGSTIn, setAlertStatus }: HeaderProps) => {
  const isMobile = useMobile();
  const { isRBACEnabled } = useValidatePermissions();

  const openGSTUpdateModal = () => {
    track({
      objectName: 'Update GST Initiate Button',
    });

    openModal({
      size: 'medium',
      isNew: true,
      component: (
        <SuspenseWithLoader>
          <UpdateModal
            gstList={gstList}
            defaultGSTIn={defaultGSTIn}
            setAlertStatus={setAlertStatus}
          />
        </SuspenseWithLoader>
      ),
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: GSTIN_UPDATE,
      },
    });
  };

  return (
    <Box
      display="flex"
      flexDirection={{
        base: 'column',
        m: 'row',
      }}
      justifyContent="space-between"
      alignItems="center"
      padding="spacing.7"
      backgroundColor="surface.background.gray.intense"
      gap="spacing.5"
    >
      {isMobile ? (
        <Box display="flex" gap="spacing.3" alignItems="center" width="100%">
          <Box
            display="flex"
            gap="spacing.3"
            padding="spacing.3"
            backgroundColor="surface.background.gray.subtle"
            borderRadius="medium"
            borderColor="surface.border.gray.muted"
          >
            <StampIcon size="medium" color="feedback.icon.neutral.intense" />
          </Box>
          <Text size="large">GST details</Text>
        </Box>
      ) : (
        <Box paddingTop="6px" paddingBottom="6px">
          <Text size="large">GST details</Text>
        </Box>
      )}
      <ValidatePermissions permissions={[PERMISSIONS.UPDATE_GST_DETAIL]}>
        <ShowWhen
          myRole={isRBACEnabled ? undefined : 'owner admin'}
          additionalCondition={(usr) => usr.isAllowedEdit('profile', isRBACEnabled)}
        >
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.3"
            justifyContent="space-between"
            alignItems="center"
            width={{
              base: '100%',
              m: 'auto',
            }}
          >
            <TriggerOnQueryParamMatch
              queryParamsMapping={[
                {
                  key: ACTION_QUERY_PARAM_KEY,
                  value: GSTIN_UPDATE,
                  trigger: openGSTUpdateModal,
                },
              ]}
            >
              <Button isFullWidth={isMobile} onClick={openGSTUpdateModal}>
                Update GST details
              </Button>
            </TriggerOnQueryParamMatch>
          </Box>
        </ShowWhen>
      </ValidatePermissions>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(Header);

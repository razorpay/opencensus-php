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
import { Heading, Button, Box, StampIcon } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { HeaderProps } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/types';
import { track } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/tracking';

const UpdateModal = lazy(() => import(/* webpackChunkName: 'UpdateModal' */ './UpdateModal'));

const Header = ({ openModal, gstList, defaultGSTIn, setAlertStatus }: HeaderProps) => {
  const isMobile = useMobile();

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
      backgroundColor="surface.background.level2.lowContrast"
      gap="spacing.5"
    >
      {isMobile ? (
        <Box display="flex" gap="spacing.3" alignItems="center" width="100%">
          <Box
            display="flex"
            gap="spacing.3"
            padding="spacing.3"
            backgroundColor="surface.background.level1.lowContrast"
            borderRadius="medium"
            borderColor="brand.gray.400.lowContrast"
          >
            <StampIcon size="medium" color="feedback.icon.neutral.lowContrast" />
          </Box>
          <Heading>GST details</Heading>
        </Box>
      ) : (
        <Box paddingTop="6px" paddingBottom="6px">
          <Heading>GST details</Heading>
        </Box>
      )}
      <ShowWhen
        myRole="owner admin"
        additionalCondition={(usr) => usr.isAllowedEdit('profile') && gstList.length}
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

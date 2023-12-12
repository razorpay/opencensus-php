import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  Box,
  Heading,
  Text,
  Card,
  CardBody,
  Switch,
  Button,
  Badge,
} from '@razorpay/blade/components';
import { useSearchParams } from 'react-router-dom';
import DeleteAccount from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/DeleteAccount';
import TurnoffNotify from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/TurnoffNotify';
import StatusNotification from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification';
import { useMobile } from 'common/hooks/useMobile';
import { updateFeatures } from 'merchant/reducers/config';
import { FEATURE_WHATSAPP_PL } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import { updateUserFeatures } from 'merchant/reducers/session';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

const modalViewMap = {
  deleteAccount: DeleteAccount,
  toggleNotification: TurnoffNotify,
  statusNotification: StatusNotification,
  close: (_: any): JSX.Element => <React.Fragment />,
};

const Dot = (): JSX.Element => (
  <Text type="subdued" size="small" weight="bold">
    •
  </Text>
);

const ActiveSetup = ({
  businessProvider,
  user,
  updateFeatures,
  showNotification,
  updateUserFeatures,
}): JSX.Element => {
  const isMobile = useMobile();
  const [searchParams, setSearchParams] = useSearchParams();
  const [modalState, setModalState] = useState({
    activeView: 'close',
    isOpen: false,
    info: {},
  });
  const [isNotificationsEnabled, setIsNotificationsEnabled] = useState<boolean>(
    user.isFeatureEnabled(FEATURE_WHATSAPP_PL),
  );

  const updateNotificationFeature = (action) => {
    updateFeatures({
      features: {
        [FEATURE_WHATSAPP_PL]: Number(action),
      },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message: `Whatsapp notifications ${action ? 'enabled' : 'disabled'} successfully`,
        });
        updateUserFeatures(FEATURE_WHATSAPP_PL, Boolean(action));
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Something went wrong in enabling Whatsapp notifications',
        });
      })
      .finally(() => {
        setIsNotificationsEnabled(user.isFeatureEnabled(FEATURE_WHATSAPP_PL));
      });
  };

  const handleAction = ({ action, eventData }): void => {
    if (action === 'toggleNotification') {
      setIsNotificationsEnabled(eventData.isChecked);
      if (eventData?.isChecked) {
        updateNotificationFeature(eventData.isChecked);
        return;
      }
    }
    setModalState((prevState) => ({
      ...prevState,
      activeView: action,
      isOpen: !(action === 'close'),
      info:
        action === 'close'
          ? {}
          : {
              ...prevState.info,
              ...eventData,
            },
    }));
  };

  useEffect(() => {
    const isSetupCompleted = searchParams.get('isWhatsappSetupCompleted');
    setSearchParams({});
    if (isSetupCompleted) {
      handleAction({
        action: 'statusNotification',
        eventData: {
          type: 'success',
        },
      });
      updateNotificationFeature(1);
    }
  }, []);

  const ModalComponent = modalViewMap[modalState.activeView];

  return (
    <>
      <Box display="flex" flexDirection="column" gap="spacing.5">
        {' '}
        <Card
          padding={isMobile ? 'spacing.5' : 'spacing.7'}
          surfaceLevel={isMobile ? 3 : 2}
          elevation="none"
        >
          <CardBody>
            <Box
              display="flex"
              justifyContent={{ m: 'space-between' }}
              alignItems={{ base: 'flex-start', m: 'center' }}
              flexDirection={{ base: 'column', m: 'row' }}
              gap={{ base: 'spacing.4', m: 'spacing.0' }}
            >
              <Box
                display="flex"
                flexDirection="column"
                gap={{ base: 'spacing.4', m: 'spacing.2' }}
              >
                <Heading size="small" weight="bold">
                  Your Whatsapp Business Account
                </Heading>
                {isMobile ? (
                  <Box display="flex" gap="spacing.3" flexDirection="column">
                    <Box display="flex" gap="spacing.2">
                      <Text type="subdued" weight="bold" size="small">
                        Business Service Provider:
                      </Text>
                      <Text type="subdued" size="small">
                        {businessProvider.title}
                      </Text>
                    </Box>
                    <Box display="flex" gap="spacing.2">
                      <Badge color="positive" size="small">
                        Account Linked
                      </Badge>
                    </Box>
                  </Box>
                ) : (
                  <Box display="flex" gap="spacing.2" alignItems="center">
                    <Text type="subdued" weight="bold">
                      Business Service Provider:
                    </Text>
                    <Text type="subdued">{businessProvider.title}</Text>
                    <Dot />
                    <Badge color="positive" size="small">
                      Account Linked
                    </Badge>
                  </Box>
                )}
              </Box>
              <Button
                size="medium"
                color="negative"
                isFullWidth={isMobile}
                onClick={() => {
                  whatsappAccountSetupAnalyticsTrack({
                    objectName: 'WA PL Account Delete Initiate',
                    actionName: 'Clicked',
                    properties: {
                      selectedBusinessAccount: businessProvider?.title,
                    },
                  });
                  handleAction({ action: 'deleteAccount', eventData: {} });
                }}
              >
                Delete
              </Button>
            </Box>
          </CardBody>
        </Card>
        <Card
          padding={isMobile ? 'spacing.5' : 'spacing.7'}
          surfaceLevel={isMobile ? 3 : 2}
          elevation="none"
        >
          <CardBody>
            {isMobile ? (
              <Box display="flex" flexDirection="column" gap="spacing.4">
                <Box display="flex" justifyContent="space-between" alignItems="flex-start">
                  <Heading size="small" weight="bold">
                    Send all payment links on Whatsapp
                  </Heading>
                  <Switch
                    isChecked={isNotificationsEnabled}
                    onChange={(data) => {
                      whatsappAccountSetupAnalyticsTrack({
                        objectName: 'WA Toggle All PLs to Whatsapp',
                        actionName: 'Clicked',
                        properties: {
                          selectedBusinessAccount: businessProvider?.title,
                          toggleButton: data.isChecked,
                        },
                      });
                      handleAction({ action: 'toggleNotification', eventData: data });
                    }}
                    accessibilityLabel="Toggle notifications"
                  />
                </Box>
                <Text size="medium" type="subtle">
                  Now, all customers will be notified about payments links over Whatsapp.
                </Text>
              </Box>
            ) : (
              <Box display="flex" justifyContent="space-between" alignItems="center">
                <Box display="flex" flexDirection="column" gap="spacing.2">
                  <Heading size="small" weight="bold">
                    Send all payment links on Whatsapp
                  </Heading>
                  <Text size="medium" type="subtle">
                    Now, all customers will be notified about payments links over Whatsapp.
                  </Text>
                </Box>
                <Switch
                  isChecked={isNotificationsEnabled}
                  onChange={(data) => {
                    whatsappAccountSetupAnalyticsTrack({
                      objectName: 'WA Toggle All PLs to Whatsapp',
                      actionName: 'Clicked',
                      properties: {
                        selectedBusinessAccount: businessProvider?.title,
                        toggleButton: data.isChecked,
                      },
                    });
                    handleAction({ action: 'toggleNotification', eventData: data });
                  }}
                  accessibilityLabel="Toggle notifications"
                />
              </Box>
            )}
          </CardBody>
        </Card>
      </Box>
      <ModalComponent
        modalState={modalState}
        setModalState={setModalState}
        onClose={handleAction}
        businessProvider={businessProvider}
        updateNotificationFeature={updateNotificationFeature}
      />
    </>
  );
};

const mapStateToProps = (state) => ({
  applications: state.applications,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { updateFeatures, updateUserFeatures, ...NotificationActions },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(ActiveSetup);

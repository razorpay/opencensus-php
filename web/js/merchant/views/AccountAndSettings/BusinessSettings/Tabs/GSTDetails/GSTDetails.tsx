import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { fetchGST } from 'merchant/reducers/profile';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { bindActionCreators } from 'redux';
import {
  Alert,
  Box,
  StampIcon,
  Text,
  Badge,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { getBusinessRegisteredAddress } from './utils';
import { useMobile } from 'common/hooks/useMobile';
import Header from './components/Header';
import { AlertStatus, AlertType, GSTDetailsProps } from './types';
import { fetchGSTList } from './model';
import { track } from './tracking';
import { GSTInErrors } from './constant';

const Item = ({ children }: { children: React.ReactNode }) => (
  <Box display="flex" flexDirection="column" gap="spacing.2">
    {children}
  </Box>
);

const GSTDetails = ({
  fetchGST,
  merchant_gst,
  rzp_gst,
  user,
  showNotification,
}: GSTDetailsProps) => {
  const isMobile = useMobile();
  const [gstList, setGSTList] = useState<string[]>([]);
  const [alertStatus, setAlertStatus] = useState<AlertStatus>();
  const [gstInError, setGstInError] = useState<string | null>(null);

  const getGSTList = async () => {
    try {
      const gstDetails = await fetchGSTList();
      if (gstDetails?.data?.results?.length) {
        setGSTList(gstDetails.data.results);
      }
    } catch {
      showNotification({
        type: 'error',
        message: 'Failed to fetch PAN linked GST numbers',
      });
    }
  };

  useEffect(() => {
    if (!user.isUnregisteredBusiness) {
      fetchGST();
      getGSTList();
    }
  }, []);

  useEffect(() => {
    if (user.isUnregisteredBusiness) {
      setGstInError(GSTInErrors.businessUnregistered);
    } else if (!gstList.length && !merchant_gst.gstin) {
      setGstInError(GSTInErrors.gstInNotLinkedToPan);
    } else {
      setGstInError(null);
    }
  }, [user.isUnregisteredBusiness, gstList.length, merchant_gst.gstin]);

  useEffect(() => {
    if (gstInError) {
      track({
        objectName: 'update gst not editable',
        actionName: 'message displayed',
        properties: {
          error: gstInError,
        },
      });
    }
  }, [gstInError]);

  if (gstInError) {
    return (
      <Box backgroundColor="surface.background.gray.intense" margin="auto" padding="spacing.7">
        <Alert
          color="negative"
          isDismissible={false}
          isFullWidth={true}
          title="GSTIN information"
          description={gstInError}
        />
      </Box>
    );
  }

  const gstIn = alertStatus?.gstIN || merchant_gst.gstin;

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      {alertStatus?.type === AlertType.SUCCESS && (
        <Alert
          color="positive"
          isDismissible={false}
          isFullWidth={true}
          title="Your GST change request was successful"
          description={`GSTIN ${alertStatus.gstIN} is now linked to your account and will appear on your invoices.`}
        />
      )}
      {alertStatus?.type === AlertType.FAILURE && (
        <Alert
          color="negative"
          isDismissible={false}
          isFullWidth={true}
          title="Your GST update request Failed"
          description="The request couldn't be completed due to a server error. We request you to Try Again after sometime."
        />
      )}
      <Box>
        <Header gstList={gstList} defaultGSTIn={gstIn} setAlertStatus={setAlertStatus} />
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          padding={{ base: 'spacing.3', m: 'spacing.4' }}
          backgroundColor="surface.background.gray.moderate"
        >
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.3"
            padding={{
              base: 'spacing.5',
              m: 'spacing.7',
            }}
            backgroundColor="surface.background.gray.intense"
          >
            <Box display="flex" gap="spacing.7" alignItems="flex-start">
              {!isMobile ? (
                <Box
                  display="flex"
                  gap="spacing.3"
                  alignItems="center"
                  padding="spacing.4"
                  backgroundColor="surface.background.gray.subtle"
                  borderRadius="medium"
                  borderColor="surface.border.gray.muted"
                >
                  <StampIcon size="medium" color="feedback.icon.neutral.intense" />
                </Box>
              ) : null}
              <Box
                display="flex"
                width="100%"
                flexWrap="wrap"
                justifyContent="space-between"
                flexDirection={{
                  base: 'column',
                  l: 'row',
                }}
                gap={{
                  base: 'spacing.7',
                  m: 'spacing.5',
                }}
              >
                <Item>
                  <Text weight="semibold">GST Number</Text>
                  <Text>{merchant_gst.gstin || '--'}</Text>
                </Item>
                <Item>
                  <Text weight="semibold">Registered Address</Text>
                  <Box maxWidth="420px">
                    <Text>{getBusinessRegisteredAddress(user)}</Text>
                  </Box>
                </Item>
                <Item>
                  <Text weight="semibold">Status</Text>
                  {gstIn ? <Badge color="positive">Active</Badge> : <Text>--</Text>}
                </Item>
                {user.isOrgRZP ? (
                  <Item>
                    <Box display="flex" gap="spacing.1" alignItems="center">
                      <Text weight="semibold">Razorpay's GST Number</Text>
                      <Tooltip content="GST number of Razorpay, to be used while filing GST returns">
                        <TooltipInteractiveWrapper>
                          <InfoIcon size="small" color="feedback.icon.neutral.intense" />
                        </TooltipInteractiveWrapper>
                      </Tooltip>
                    </Box>
                    <Text>{rzp_gst.gstin}</Text>
                  </Item>
                ) : null}
              </Box>
            </Box>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => {
  return {
    ...state.profile,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchGST,
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(GSTDetails);

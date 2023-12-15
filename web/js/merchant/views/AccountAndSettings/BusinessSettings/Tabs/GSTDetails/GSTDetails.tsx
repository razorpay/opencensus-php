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

  if (user.isUnregisteredBusiness || !gstList.length) {
    return (
      <Box
        backgroundColor="surface.background.level2.lowContrast"
        margin="auto"
        padding="spacing.7"
      >
        <Alert
          color="negative"
          isDismissible={false}
          isFullWidth={true}
          title="GSTIN information"
          description={
            user.isUnregisteredBusiness
              ? 'GST addition is not supported for your business type. You can create a new Razorpay Account as a Non- Individual business type and link GST to it.'
              : 'There is no GSTIN currently linked to your provided PAN number. Either link GSTIN to your PAN or Create a new Razorpay account with a GSTIN linked PAN.'
          }
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
          backgroundColor="surface.background.level3.lowContrast"
        >
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.3"
            padding={{
              base: 'spacing.5',
              m: 'spacing.7',
            }}
            backgroundColor="surface.background.level2.lowContrast"
          >
            <Box display="flex" gap="spacing.7" alignItems="flex-start">
              {!isMobile ? (
                <Box
                  display="flex"
                  gap="spacing.3"
                  alignItems="center"
                  padding="spacing.4"
                  backgroundColor="surface.background.level1.lowContrast"
                  borderRadius="medium"
                  borderColor="brand.gray.400.lowContrast"
                >
                  <StampIcon size="medium" color="feedback.icon.neutral.lowContrast" />
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
                  <Text weight="bold">GST Number</Text>
                  <Text>{merchant_gst.gstin || '--'}</Text>
                </Item>
                <Item>
                  <Text weight="bold">Registered Address</Text>
                  <Box maxWidth="420px">
                    <Text>{getBusinessRegisteredAddress(user)}</Text>
                  </Box>
                </Item>
                <Item>
                  <Text weight="bold">Status</Text>
                  {gstIn ? <Badge color="positive">Active</Badge> : <Text>--</Text>}
                </Item>
                {user.isOrgRZP ? (
                  <Item>
                    <Box display="flex" gap="spacing.1" alignItems="center">
                      <Text weight="bold">Razorpay's GST Number</Text>
                      <Tooltip content="GST number of Razorpay, to be used while filing GST returns">
                        <TooltipInteractiveWrapper>
                          <InfoIcon size="small" color="feedback.icon.neutral.lowContrast" />
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

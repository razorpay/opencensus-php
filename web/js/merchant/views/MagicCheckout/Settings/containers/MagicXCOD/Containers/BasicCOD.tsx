import React, { useMemo, useCallback, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  Box,
  Heading,
  Text,
  Alert,
  Link,
  InfoIcon,
  Tooltip,
  IconButton,
  Button,
  RefreshIcon,
  CheckCircleIcon,
  LoaderIcon,
} from '@razorpay/blade/components';
import { CODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/components/Table';
import EditModal from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/components/Modal';
import { SetupGuide } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/SetupGuide';
import { CODTableShimmer } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/components/CODTableShimmer';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import Time from 'common/ui/Time';
import { LastSyncedBadge } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/styled';

import {
  convertTableDataToForm,
  convertServerDataToTableData,
  getLastSyncedWithShopifyInMs,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/helpers';
import {
  setProfile,
  clearProfile,
  fetchSummary as fetchShippingProfiles,
} from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  syncWithShopify,
  pollShippingProfiles,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/api';
import { useFormContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/Context';
import { useConfirm } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

import {
  action,
  COD_TABLE_TITLE,
  COLUMNS,
  BASIC_COD_SETUP_GUIDE,
  SYNC_STATES,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/constants';
import {
  Column,
  SyncStates,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

const MAX_POLLING_ATTEMPTS = 10;
const POLLING_INTERVAL = 6000;

const BasicCOD = ({
  magicSettings,
  magicShippingEngine,
  setProfile,
  clearProfile,
  fetchShippingProfiles,
  showNotification,
  dashboardView,
}): JSX.Element => {
  const { shipping_profiles, isLoading } = magicShippingEngine;
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [syncState, setSyncState] = useState<SyncStates>(SYNC_STATES.IDLE);
  const { initialiseFormData, resetForm } = useFormContext();
  const confirm = useConfirm();

  const notify = (type, message) =>
    showNotification({ type, message: <DisplayNotificationTxt notificationTxt={message} /> });

  const tableData = useMemo(() => {
    if (Object.keys(shipping_profiles)?.length) {
      return convertServerDataToTableData(shipping_profiles);
    }
    return [];
  }, [shipping_profiles]);

  const lastUpdatedAt = useMemo(() => {
    if (Object.keys(shipping_profiles)?.length) {
      return getLastSyncedWithShopifyInMs(shipping_profiles);
    }
    return null;
  }, [shipping_profiles]);

  const handleEdit = useCallback((item) => {
    initialiseFormData(convertTableDataToForm(item));
    setIsModalOpen(true);
    setProfile(item?.profileName);
  }, []);

  const columns: Column[] = useMemo(
    () => [...Object.keys(COLUMNS).map((COLUMN) => COLUMNS[COLUMN]), action(handleEdit)],
    [],
  );

  const handleModalClose = useCallback(() => {
    setIsModalOpen(false);
    clearProfile();
    resetForm();
  }, []);

  const syncStateMapping = useMemo(() => {
    switch (syncState) {
      case SYNC_STATES.LOADING:
        return {
          icon: LoaderIcon,
          text: 'Syncing from Shopify',
        };
      case SYNC_STATES.SUCCESS:
        return {
          icon: CheckCircleIcon,
          text: 'Synced',
        };
      default:
        return {
          icon: RefreshIcon,
          text: 'Sync Again',
        };
    }
  }, [syncState]);

  const initiatePolling = () => {
    let attempts = 0;

    const poll = async () => {
      try {
        attempts += 1;
        const { status_code } = (await pollShippingProfiles()) || {};

        if (status_code === 200) {
          fetchShippingProfiles();
          setSyncState(SYNC_STATES.SUCCESS);
          notify('success', 'Shipping Profiles are successfully fetched from Shopify');
          return; // End polling
        } else if (status_code === 204) {
          if (attempts < MAX_POLLING_ATTEMPTS) {
            setTimeout(poll, POLLING_INTERVAL); // Continue polling every 6 seconds
          } else {
            setSyncState(SYNC_STATES.IDLE);
            notify(
              'neutral',
              'Sync process is taking more time than expected. Please refresh or try again later.',
            );
          }
        } else {
          setSyncState(SYNC_STATES.IDLE);
          notify('error', 'Unexpected Status Code. Please try again later.');
        }
      } catch (err: any) {
        setSyncState(SYNC_STATES.IDLE);
        notify('error', `${err?.errors?.[0] || 'Something went wrong'}`);
      }
    };
    setTimeout(poll, POLLING_INTERVAL);
  };

  const handleSync = async () => {
    const shouldSync = await confirm({
      title: 'Sync Shipping Profiles From Shopify',
      description: 'Syncing Shipping Profiles from Shopify might take upto 20 seconds.',
      confirmText: 'Sync',
      confirmAccessibilityLabel: 'confirm sync',
    });

    if (!shouldSync) return;

    setSyncState(SYNC_STATES.LOADING);
    syncWithShopify(dashboardView)
      .then((res) => {
        if (res.status_code === 202) {
          initiatePolling();
        } else {
          setSyncState(SYNC_STATES.IDLE);
          notify('error', 'Unexpected Status Code. Please try again later.');
        }
      })
      .catch((err) => {
        setSyncState(SYNC_STATES.IDLE);
        notify('error', `${err?.errors[0] || 'Something went wrong'}`);
      });
  };

  return (
    <>
      <Alert
        marginY="spacing.6"
        color="notice"
        isDismissible={false}
        description={
          <span>
            COD can be configured for Shipping Methods created on Shopify. <b>Sync Again</b> if you
            don’t see all your shipping methods from Shopify in the table below. Refer to the{' '}
            <Link href={BASIC_COD_SETUP_GUIDE.docs} target="_blank" size="small">
              Setup Guide
            </Link>{' '}
            for detailed steps or contact support at checkout360-support@razorpay.com
          </span>
        }
        isFullWidth
        icon={() => <InfoIcon color="feedback.icon.notice.intense" />}
      />
      {magicSettings.rcod?.enabled && (
        <>
          <Box display="flex" justifyContent="space-between" margin="spacing.3">
            <Box display="flex" alignItems="center">
              <Heading size="medium" marginRight="spacing.3">
                {COD_TABLE_TITLE.title}
              </Heading>
              <Tooltip content={COD_TABLE_TITLE.tooltip}>
                <IconButton
                  accessibilityLabel="Info about COD Table"
                  icon={InfoIcon}
                  onClick={() => ''}
                />
              </Tooltip>
            </Box>
            <Box display="flex" alignItems="center">
              {tableData?.length ? (
                <LastSyncedBadge data-testid="last-synced-badge">
                  <Text size="small">Last synced: </Text>
                  {lastUpdatedAt ? (
                    <Time key={`timer-${lastUpdatedAt}`} value={lastUpdatedAt} relative />
                  ) : (
                    'N/A'
                  )}
                </LastSyncedBadge>
              ) : null}
              <Button
                color={syncState === SYNC_STATES.SUCCESS ? 'positive' : 'primary'}
                variant="secondary"
                icon={syncStateMapping.icon}
                iconPosition="left"
                isDisabled={syncState === SYNC_STATES.LOADING}
                onClick={handleSync}
                accessibilityLabel="Sync profiles from Shopify"
              >
                {syncStateMapping.text}
              </Button>
            </Box>
          </Box>
          {syncState === SYNC_STATES.LOADING ? (
            <CODTableShimmer />
          ) : tableData?.length || isLoading?.summary ? (
            <CODTable
              shippingMethods={tableData}
              isLoading={isLoading?.summary}
              columns={columns}
            />
          ) : (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="100px">
              <Text>Shipping Methods Not Found On Shopify</Text>
            </Box>
          )}
        </>
      )}
      <Box marginTop="spacing.9">
        <SetupGuide {...BASIC_COD_SETUP_GUIDE} />
      </Box>
      <EditModal isOpen={isModalOpen} handleModalClose={handleModalClose} />
    </>
  );
};

const mapStateToProps = (state) => ({
  magicSettings: state.magic_settings,
  magicShippingEngine: state.magicShippingEngine,
  dashboardView: state.magicCheckout.dashboard_view,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setProfile,
      clearProfile,
      fetchShippingProfiles,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(BasicCOD);

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
} from '@razorpay/blade/components';
import { CODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Components/Table';
import EditModal from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Components/Modal';
import { SetupGuide } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Components/SetupGuide';

import {
  convertTableDataToForm,
  convertServerDataToTableData,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/helpers';
import { setProfile, clearProfile } from 'merchant/reducers/magicCheckout/shippingEngine/action';

import { useFormContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Context';

import {
  action,
  COD_TABLE_TITLE,
  COLUMNS,
  SETUP_GUIDE_DOCS_HREF,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';
import { Column } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

const BasicCOD = ({
  magicSettings,
  magicShippingEngine,
  setProfile,
  clearProfile,
}): JSX.Element => {
  const { shipping_profiles, isLoading } = magicShippingEngine;
  const [isModalOpen, setIsModalOpen] = useState(false);
  const { initialiseFormData, resetForm } = useFormContext();

  const tableData = useMemo(() => {
    if (Object.keys(shipping_profiles)?.length) {
      return convertServerDataToTableData(shipping_profiles);
    }
    return [];
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

  return (
    <>
      <Alert
        marginY="spacing.6"
        color="notice"
        isDismissible={false}
        description={
          <span>
            COD can be configured for Shipping Methods created on Shopify. The following methods are
            synced from your Shopify store. Refer to the{' '}
            <Link href={SETUP_GUIDE_DOCS_HREF} target="_blank" size="small">
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
          <Box display="flex" alignItems="center">
            <Heading size="medium" marginRight="spacing.3">
              {COD_TABLE_TITLE.title}
            </Heading>
            <Tooltip content={COD_TABLE_TITLE.tooltip}>
              <IconButton accessibilityLabel="info" icon={InfoIcon} onClick={() => ''} />
            </Tooltip>
          </Box>
          {tableData?.length || isLoading?.summary ? (
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
        <SetupGuide />
      </Box>
      <EditModal isOpen={isModalOpen} handleModalClose={handleModalClose} />
    </>
  );
};

const mapStateToProps = (state) => ({
  magicSettings: state.magic_settings,
  magicShippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setProfile,
      clearProfile,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(BasicCOD);

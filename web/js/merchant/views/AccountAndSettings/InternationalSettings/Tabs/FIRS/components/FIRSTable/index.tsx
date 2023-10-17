import React, { useEffect } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

import DropdownAction from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/DropdownAction';
import FirsList from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/FirsList';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import { FirsTablePropsT } from 'merchant/views/AccountAndSettings/InternationalSettings/typings';
import { showNotification } from 'merchant_common/reducers/notifications';

const FIRSTable = ({ showNotification }: FirsTablePropsT) => {
  const { getFirsData, error, setError } = useFirsContext();

  useEffect(() => {
    if (error) {
      showNotification({ type: 'error', message: error.toString() });
      setError(null);
    }
  }, [error]);

  useEffect(() => {
    getFirsData(new Date().getFullYear());
  }, []);

  return (
    <Box>
      <Box
        as="section"
        display="flex"
        flexDirection={{ base: 'row' }}
        alignItems={{ base: 'flex-end' }}
        justifyContent={{ base: 'space-between' }}
        marginBottom={{ base: 'spacing.5', m: 'spacing.8' }}
        paddingTop={{ base: 'spacing.4' }}
      >
        <DropdownAction />
        <Box display={{ m: 'flex', base: 'none' }} alignItems={{ base: 'center' }} height="36px">
          <Text size="small" textAlign="right">
            Note: FIRS are only available for months with at least one international payment
          </Text>
        </Box>
      </Box>
      <Box as="section" display="flex" flexDirection="column">
        <FirsList />
      </Box>
    </Box>
  );
};

export default compose(
  connect(null, {
    showNotification,
  }),
)(FIRSTable);

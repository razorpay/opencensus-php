import React, { useState } from 'react';
import { Box, Button, Heading, Text, TextInput } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SalesPocIcon from 'assets/pos/icons/sales-poc.svg';
import { updateSalePoc } from 'merchant/views/POS/services';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { SalesPocBannerContainer } from './styles';

type SalesPocBannerProps = {
  orderId: string;
  showNotification: (args) => void;
  onSalesPocUpdate: (pocCode: string) => void;
};

const SalesPocBanner = ({
  orderId,
  onSalesPocUpdate,
  showNotification,
}: SalesPocBannerProps): JSX.Element => {
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [pocCode, setPocCode] = useState<string>('');

  const handleError = () => {
    showNotification({
      type: 'error',
      message: 'Something went wrong! Please try again.',
    });
  };

  const handleOnSubmit = async () => {
    setIsLoading(true);
    try {
      const response = await updateSalePoc({ id: orderId, pocCode });
      if (response?.data) {
        showNotification({
          type: 'success',
          message: 'Sales POC updated successfully',
        });
        onSalesPocUpdate?.(response?.data);
        return;
      }
      handleError();
    } catch {
      handleError();
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SalesPocBannerContainer>
      <Box
        display={{ base: 'block', l: 'flex' }}
        justifyContent="space-between"
        alignItems="center"
      >
        <Box display="flex">
          <Box marginRight="spacing.4">
            <img src={SalesPocIcon} alt="sales poc icon" height="30" />
          </Box>
          <Box>
            <Heading marginBottom="spacing.1">
              Have you been assisted by our Sales Executive?
            </Heading>
            <Text type="subdued" marginBottom={{ base: 'spacing.5', l: '0px' }}>
              If so, please enter the code provided by the them
            </Text>
          </Box>
        </Box>
        <Box>
          <Box display="flex" alignItems="flex-end">
            <Box minWidth={{ base: 'auto', m: '270px' }} marginRight="spacing.5">
              <TextInput
                name="sales-poc-code"
                label="Sales Executive Code"
                placeholder="Enter Code"
                onChange={({ value }) => setPocCode(value ?? '')}
              />
            </Box>
            <Button onClick={handleOnSubmit} isLoading={isLoading}>
              Submit
            </Button>
          </Box>
        </Box>
      </Box>
    </SalesPocBannerContainer>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...NotificationsActions }, dispatch);
};

export default connect(null, mapDispatchToProps)(SalesPocBanner);

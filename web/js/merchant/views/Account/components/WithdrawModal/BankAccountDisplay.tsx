import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { Box, CurrentAccountIcon, Skeleton, Text } from '@razorpay/blade/components';

function BankAccountDetails({ bankAccount }) {
  const accNumberString = () => {
    if (bankAccount.account_number) {
      const maskedBankAccountString = '********' + bankAccount.account_number.slice(-4);
      return maskedBankAccountString;
    }
    return '';
  };
  return (
    <>
      {!bankAccount?.account_number ? (
        <Box display="flex" gap="16px">
          <Box height="42px" width="42px" borderRadius="medium" overflow="hidden">
            <Skeleton height="100%" width="100%" />
          </Box>
          <Box display="flex" gap="2px" flexDirection="column">
            <Skeleton width="150px" height="20px" borderRadius="small" />
            <Skeleton width="80px" height="18px" borderRadius="small" />
          </Box>
        </Box>
      ) : (
        <Box display="flex" gap="16px">
          <Box
            height="42px"
            width="42px"
            borderRadius="medium"
            overflow="hidden"
            borderWidth="thin"
            borderColor="surface.border.gray.subtle"
            display="flex"
            alignItems="center"
            justifyContent="center"
          >
            <CurrentAccountIcon size="large" />
          </Box>
          <Box display="flex" gap="2px" flexDirection="column">
            <Text color="surface.text.gray.normal" size="medium" weight="regular">
              {bankAccount.bank_name}
            </Text>
            <Text color="surface.text.gray.muted" size="medium" weight="regular">
              {accNumberString()}
            </Text>
          </Box>
        </Box>
      )}
    </>
  );
}

const mapStateToProps = (state) => ({
  bankAccount: state.profile.bankAccount,
});
export default compose(connect(mapStateToProps, null))(BankAccountDetails);

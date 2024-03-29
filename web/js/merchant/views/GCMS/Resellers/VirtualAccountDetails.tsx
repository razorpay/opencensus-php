import React from 'react';
import { Box, Text, CopyIcon, IconButton, InfoIcon, Divider } from '@razorpay/blade/components';
import CustomClipboard from 'common/ui/Clipboard/Custom'; // eslint-disable-line

interface VirtualAccountDetailsProps {
  accountNumber?: string;
  ifsc?: string;
  beneficiaryName?: string;
}

const VirtualAccountDetails = ({
  accountNumber,
  ifsc,
  beneficiaryName,
}: VirtualAccountDetailsProps) => {
  return (
    <div>
      <Box
        marginTop={'spacing.5'}
        marginLeft={'spacing.6'}
        marginBottom={'spacing.8'}
        backgroundColor={'surface.background.gray.intense'}
        borderColor="surface.border.gray.muted"
        borderWidth={'thin'}
        maxWidth={'50%'}
        borderRadius={'medium'}
      >
        <Box marginTop={'spacing.4'} marginX={'spacing.6'}>
          <Text weight="semibold" color="surface.text.gray.subtle">
            Account Details
          </Text>
        </Box>

        <Divider marginY="spacing.4" />

        <Box
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          display="flex"
          marginX={'spacing.6'}
          marginBottom={'spacing.6'}
          justifyContent={'space-between'}
        >
          <Box>
            <Text color="surface.text.gray.subtle">Account number</Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              IFSC Number
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              Beneficiary Name
            </Text>
          </Box>
          <Box marginRight={'spacing.8'}>
            <Text weight="semibold" color="surface.text.gray.subtle">
              {accountNumber || ''}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {ifsc || ''}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {beneficiaryName || ''}
            </Text>
          </Box>
          <Box
            flexDirection={{
              base: 'column',
              m: 'column',
            }}
            display="flex"
          >
            <CustomClipboard value={accountNumber}>
              <IconButton
                size="large"
                accessibilityLabel="filter"
                icon={CopyIcon}
                onClick={() => {}}
              />
            </CustomClipboard>
            <Box marginTop={'spacing.5'}>
              <CustomClipboard value={ifsc}>
                <IconButton
                  size="large"
                  accessibilityLabel="filter"
                  icon={CopyIcon}
                  onClick={() => {}}
                />
              </CustomClipboard>
            </Box>
          </Box>
        </Box>

        <Box
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          display="flex"
          backgroundColor={'surface.background.primary.subtle'}
          paddingY={'spacing.5'}
        >
          <InfoIcon marginLeft={'spacing.6'} />
          <Text size="small" marginLeft={'spacing.6'} color="surface.text.gray.subtle">
            Bank transfer can be performed to top up the account for placing orders
          </Text>
        </Box>
      </Box>
    </div>
  );
};

export default VirtualAccountDetails;

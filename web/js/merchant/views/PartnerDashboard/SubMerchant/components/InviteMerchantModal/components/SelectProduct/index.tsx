import React from 'react';
import { Box, Button, Radio, RadioGroup, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { useI18Service } from 'common/i18';
import { User } from 'common/typings';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE, PRODUCT_NAME } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

// TODO v2: maintainence status props and payments disable note
// const PAYMENTS_MAINTENANCE_STATUS = {
//   rzp: true,
//   curlec: false,
// };
// const PAYMENTS_DISABLED_STATUS = {
//   rzp: false,
//   curlec: false,
// };

type SelectProductProps = {
  user: User;
  onNextClick: () => void;
  setProductType: (args: string) => void;
  productType: string;
  orgName: string;
};
const SelectProduct = ({
  user,
  orgName,
  productType,
  setProductType,
  onNextClick,
}: SelectProductProps): JSX.Element => {
  const handleSelectProduct = ({ value }) => {
    setProductType(value);
  };
  const { isConfigTagEnabled } = useI18Service();
  const { isPartnershipsForPosEnabled } = usePartnerDashboardExperiments();
  const xProductName = PRODUCT_NAME[PRODUCT_TYPE.X];
  return (
    <Box display="flex" flexDirection="column" gap="spacing.6" flex="1" minHeight="425px">
      <RadioGroup onChange={handleSelectProduct} value={productType} size="small" label="">
        <Box
          display="flex"
          flexDirection="column"
          justifyContent="center"
          backgroundColor="surface.background.level2.lowContrast"
        >
          <div onClick={() => setProductType(PRODUCT_TYPE.PG)}>
            <Box
              display="flex"
              flexDirection="column"
              gap="spacing.5"
              justifyContent="center"
              padding="spacing.6"
              backgroundColor="surface.background.level2.lowContrast"
              borderColor="surface.border.normal.lowContrast"
              borderWidth="thin"
            >
              <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                <Box display="flex" flexDirection="column" gap="spacing.2" justifyContent="center">
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      <Text weight="bold">{orgName} Payments</Text>
                      <Text size="small">
                        Refer merchants to {orgName} Payment gateway and other products to receive
                        payments
                      </Text>
                    </Box>
                  </Box>
                </Box>
                <Radio value={PRODUCT_TYPE.PG}>{''}</Radio>
              </Box>
            </Box>
          </div>
          {isPartnershipsForPosEnabled ? (
            <div onClick={() => setProductType(PRODUCT_TYPE.POS)}>
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                justifyContent="center"
                padding="spacing.6"
                backgroundColor="surface.background.level2.lowContrast"
                borderColor="surface.border.normal.lowContrast"
                borderWidth="thin"
              >
                <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.2"
                    justifyContent="center"
                  >
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        <Text weight="bold">{orgName} POS</Text>
                        <Text size="small">
                          Refer merchants to {orgName} Payment gateway and other products to receive
                          payments
                        </Text>
                      </Box>
                    </Box>
                  </Box>
                  <Radio value={PRODUCT_TYPE.POS}>{''}</Radio>
                </Box>
              </Box>
            </div>
          ) : null}
          {!isPartnershipsForPosEnabled &&
          !isConfigTagEnabled('partnership.add_new_razorpay_x_merchant') ? (
            <div onClick={() => setProductType(PRODUCT_TYPE.X)}>
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                justifyContent="center"
                padding="spacing.6"
                backgroundColor="surface.background.level2.lowContrast"
                borderColor="surface.border.normal.lowContrast"
                borderWidth="thin"
              >
                <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.2"
                    justifyContent="center"
                  >
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        <Text weight="bold">{xProductName}</Text>
                        <Text size="small">
                          Refer merchants to {xProductName} products like Current account to process
                          payouts
                        </Text>
                      </Box>
                    </Box>
                  </Box>
                  <Radio value={PRODUCT_TYPE.X}>{''}</Radio>
                </Box>
              </Box>
            </div>
          ) : null}

          {user.isPartnershipForCapitalEnabled ? (
            <div onClick={() => setProductType(PRODUCT_TYPE.CAPITAL)}>
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                justifyContent="center"
                padding="spacing.6"
                backgroundColor="surface.background.level2.lowContrast"
                borderColor="surface.border.normal.lowContrast"
                borderWidth="thin"
              >
                <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.2"
                    justifyContent="center"
                  >
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        <Text weight="bold">Line of credit</Text>
                        <Text size="small">
                          Refer merchants to {orgName} Payment gateway and other products to receive
                          payments
                        </Text>
                      </Box>
                    </Box>
                  </Box>
                  <Radio value={PRODUCT_TYPE.CAPITAL}>{''}</Radio>
                </Box>
              </Box>
            </div>
          ) : null}
        </Box>
      </RadioGroup>
      <ModalFooter>
        <Button onClick={onNextClick}>Next</Button>
      </ModalFooter>
    </Box>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({}, dispatch),
  ),
)(SelectProduct);

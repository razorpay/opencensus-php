import React, { useEffect } from 'react';
import SwitchField from 'common/ui/Forms/SwitchField';
import withInternationalConfig from 'merchant/views/Settings/Configuration/InternationalConfig';
import Non3dsCardsActivation from './Non3dsCardsActivation';
import { trackIsButtonVisible } from 'merchant/views/Settings/Configuration/Questionnaire/analytics';
import { Box, Text } from '@razorpay/blade/components';
import {
  InternationalPayments,
  ProductInfo,
} from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments';

const International = ({
  config: {
    isKycComplete,
    isWebsiteAdded,
    showStatusLabel,
    maxPaymentAmount,
    isTogglerVisible,
    questionnaireStatus,
    internationalEnabled,
    currentStatusOnHeader,
    isRequestAccessAllowed,
    isInternationalBlackList,
    isAnyProductIntlApproved,
  },
  productStatus,
  settlementDelay,
  onRequestAccessClick,
  toggleInternationalization,
}) => {
  useEffect(() => {
    trackIsButtonVisible(isRequestAccessAllowed && !isInternationalBlackList && isWebsiteAdded);
  }, [isRequestAccessAllowed]);

  if (!isWebsiteAdded || isInternationalBlackList || !internationalEnabled) {
    return (
      <InternationalPayments
        config={{
          isKycComplete,
          isWebsiteAdded,
          questionnaireStatus,
          currentStatusOnHeader,
          isRequestAccessAllowed,
          isInternationalBlackList,
        }}
        onRequestAccessClick={onRequestAccessClick}
      />
    );
  }

  return (
    <li className="international-leaf-item">
      <Box>
        <Box>
          <Text as="span" weight="bold">
            International Cards
          </Text>
          <Text>On Payment Gateway, Pages, Links and Invoices</Text>
        </Box>
        {isTogglerVisible && (
          <Box
            display="flex"
            marginLeft="spacing.4"
            alignItems="center"
            gap="spacing.2"
            alignSelf="flex-start"
          >
            <SwitchField
              defaultChecked={internationalEnabled}
              onChange={(isChecked, postActionCB) => {
                toggleInternationalization(isChecked, postActionCB);
              }}
              type="prime"
            />
            <Text as="span" weight="bold" color="action.text.link.default">
              Enabled
            </Text>
          </Box>
        )}
      </Box>

      {internationalEnabled && <Non3dsCardsActivation />}

      {!!isAnyProductIntlApproved && (
        <>
          <Box height="spacing.4" />

          <ProductInfo
            product="pg"
            title="On Payment Gateway"
            settlementCycle={settlementDelay}
            showStatusLabel={showStatusLabel}
            transactionSize={maxPaymentAmount}
            status={productStatus.pg.status}
            showRequestAccessBtn={productStatus.pg.isRequested}
            onRequestAccessClick={() => onRequestAccessClick({ triggerSource: 'pg' })}
            disabled={!isKycComplete}
          />

          <Box height="spacing.4" />

          <ProductInfo
            product="otherProducts"
            showStatusLabel={showStatusLabel}
            settlementCycle={settlementDelay}
            transactionSize={maxPaymentAmount}
            title="Payment Pages, Links and Invoices"
            status={productStatus.otherProducts.status}
            showRequestAccessBtn={productStatus.otherProducts.isRequested}
            onRequestAccessClick={() => onRequestAccessClick({ triggerSource: 'otherProducts' })}
          />
        </>
      )}
    </li>
  );
};

export default withInternationalConfig(International);

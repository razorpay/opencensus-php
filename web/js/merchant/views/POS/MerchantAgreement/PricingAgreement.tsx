import React from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { getOrg, getUser, useStore } from '@federated/apps/shell/commonStore';

import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';
import sanitizer from 'common/utils/xss-sanitizer';
import {
  MDR_VAS_RATES_COMPONENT,
  getPosPricingTemplate,
  PRICING_AGREEMENT_FAILED_TO_LOAD,
  PRICING_STEP,
  VAS_RATES_COMPONENT,
} from 'merchant/views/POS/constants';
import {
  getAllFieldValuesOfAComponent,
  getComponentByName,
  getDeviceChargesData,
  getStepDataByStepName,
} from 'merchant/views/POS/helpers';
import { getModularOnboardingData } from 'merchant/views/POS/services';
import { ErrorBoundaryFallBackComponent } from 'merchant/widgets/utils';

import { StyledPricingTemplateContainer } from './styles';

const PricingAgreement = (): JSX.Element => {
  const showNotification = useStore((state) => state.showNotification);
  const session = getUser();
  const org = getOrg();
  const { data: workflowConfig, isLoading } = useQuery({
    queryKey: ['posAgreement'],
    queryFn: () => getModularOnboardingData(session?.merchant?.id ?? ''),
    retryDelay: 800,
    refetchOnWindowFocus: false,
    cacheTime: 1000 * 60 * 1,
    refetchOnMount: 'always',
  });

  const replacePlaceholders = (htmlString: string, values) => {
    if (!values) {
      showNotification({
        type: 'error',
        message: PRICING_AGREEMENT_FAILED_TO_LOAD,
      });
      throw new Error('No pricing values found');
    }
    return htmlString.replace(/{{(\w+)}}/g, (match, p1) => {
      return values[p1] || '';
    });
  };

  const getPricingStepValues = () => {
    const pricingStep = getStepDataByStepName(workflowConfig?.data, PRICING_STEP);
    if (!pricingStep) {
      showNotification({ type: 'error', message: PRICING_AGREEMENT_FAILED_TO_LOAD });
      throw new Error('No pricing step found');
    }
    const mdrComponent =
      getComponentByName(pricingStep.components, MDR_VAS_RATES_COMPONENT) ||
      getComponentByName(pricingStep.components, VAS_RATES_COMPONENT);
    if (!mdrComponent) {
      showNotification({ type: 'error', message: PRICING_AGREEMENT_FAILED_TO_LOAD });
      throw new Error('No mdr or vas rates component found');
    }
    const allFieldData = getAllFieldValuesOfAComponent(mdrComponent);
    return allFieldData;
  };

  const {
    tableStructure,
    data: devices,
    overallSetupFee,
  } = getDeviceChargesData(workflowConfig?.data);

  if (isLoading)
    return (
      <Box
        as="section"
        height="90vh"
        width="100%"
        display="flex"
        alignItems="center"
        justifyContent="center"
      >
        <Spinner color="primary" accessibilityLabel="pos-agreement-spinner" size="xlarge" />
      </Box>
    );
  const heading = tableStructure[0];
  const deviceChargeTable = `<table>
      <thead>
        <tr>
          <th>${heading[0]}</th>
          ${devices
            .map((item) => {
              return `<th key=${item.item_id}>${heading[1](item)}</th>`;
            })
            .join(' ')}
        </tr>
      </thead>
      <tbody>
        ${tableStructure
          ?.filter((_item, idx) => idx !== 0)
          .map(([title, valueFn]) => {
            return `<tr key=${title}>
                <td>${title}</td>
                ${devices
                  .map((item) => {
                    return `<td key=${item.item_id}>${valueFn(item)}</td>`;
                  })
                  .join(' ')}
              </tr>`;
          })
          .join(' ')}
        <tr id="overall-rental-fee">
          <td>Overall Fee Payable at Setup</td>
          ${
            devices.length
              ? `<td colspan=${devices.length}>${overallSetupFee?.toFixed(2)}</td>`
              : '<td></td>'
          }
        </tr>
      </tbody>
    </table>`;
  return (
    <ErrorBoundary rank={Ranks.P0} FallbackComponent={ErrorBoundaryFallBackComponent}>
      <Box>
        <StyledPricingTemplateContainer>
          <div
            dangerouslySetInnerHTML={{
              __html: sanitizer(
                replacePlaceholders(
                  getPosPricingTemplate(org, deviceChargeTable),
                  getPricingStepValues(),
                ),
              ),
            }}
          />
        </StyledPricingTemplateContainer>
      </Box>
    </ErrorBoundary>
  );
};

export default PricingAgreement;

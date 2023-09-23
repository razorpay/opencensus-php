import React, { useState, useEffect } from 'react';
import {
  Box,
  Link,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  TextInput,
  Text,
  RupeeIcon,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Amount,
  useTheme,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import {
  ACTION_QUERY_PARAM_KEY,
  CHECK_FPR_SAVING,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { removeQueryParam, getSanitizeValue } from './util';

const ORDER_RATE = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

const DesktopBreakpoints = ['xl', 'l'];

const FailedPaymentsRetry = (): JSX.Element => {
  const { theme } = useTheme();
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const [formData, setFormData] = useState<Record<string, string>>({
    failedOrder: '',
    averageOrderValue: '',
    revivalRate: '3',
  });
  const [GMV, setGMV] = useState<string>();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = DesktopBreakpoints.includes(matchedBreakpoint as string);

  const handleFPRClick = (): void | boolean => isDesktop && setIsOpen(true);

  const handleDismiss = (): void => {
    setFormData({
      failedOrder: '',
      averageOrderValue: '',
      revivalRate: '3',
    });
    setGMV('');
    removeQueryParam();
    setIsOpen(false);
  };

  const handleInputChange = ({ name = '', value = '' }): void => {
    const sanitizeValue = getSanitizeValue(value);
    if (name && !isNaN(Number(sanitizeValue))) {
      setFormData((prevState) => ({
        ...prevState,
        [name]: sanitizeValue,
      }));
    }
  };

  const calculateFPR = (): void => {
    const isFormReady = Object.values(formData).filter(Boolean).length === 3;
    if (isFormReady) {
      const totalOrders = Object.keys(formData).reduce((acc, entity) => {
        const data = parseFloat(formData[entity]);
        if (entity !== 'revivalRate') {
          acc = acc * data;
        }
        return acc;
      }, 1 as number);
      const totalSavings = (totalOrders * (parseInt(formData.revivalRate, 10) / 100)).toFixed(2);
      setGMV(totalSavings);
    } else {
      setGMV('');
    }
  };

  useEffect(() => {
    calculateFPR();
  }, [...Object.values(formData)]);

  return (
    <TriggerOnQueryParamMatch
      queryParamsMapping={[
        {
          key: ACTION_QUERY_PARAM_KEY,
          value: CHECK_FPR_SAVING,
          trigger: handleFPRClick,
        },
      ]}
    >
      <Box>
        {isDesktop ? (
          <Link size="medium" variant="button" onClick={handleFPRClick}>
            To view the FPR calculator, click here
          </Link>
        ) : null}
        <Modal isOpen={isOpen} onDismiss={handleDismiss} zIndex={1112}>
          <ModalHeader title="Calculate the recovered GMV" />
          <ModalBody>
            <Box display="flex" flexDirection="column" gap="spacing.7">
              <Text size="medium" type="normal">
                To assess the recovery potential for your business, enter the values to calculate
                the monthly revenue you could have earned from your failed payments
              </Text>
              <TextInput
                name="failedOrder"
                type="number"
                helpText="total failed orders per month approx for your business"
                label="Number of failed orders"
                value={formData.failedOrder}
                onChange={handleInputChange}
                placeholder="Enter value here"
              />
              <TextInput
                name="averageOrderValue"
                type="number"
                helpText="Average order value is average amount spent by customers per transaction"
                label="Average order value"
                value={formData.averageOrderValue}
                icon={RupeeIcon}
                onChange={handleInputChange}
                placeholder="Enter value here"
              />
              <Dropdown selectionType="single">
                <SelectInput
                  name="revivalRate"
                  label="Order revival rate"
                  placeholder="Select order rate"
                  defaultValue="3"
                  onChange={({ name, values }) => handleInputChange({ name, value: values[0] })}
                  helpText="Order revival rate is the percentage of customers who retry the payment with a payment success"
                />
                <DropdownOverlay>
                  <ActionList>
                    {ORDER_RATE.map((each, key) => (
                      <ActionListItem key={key} title={`${each}%`} value={each} />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            </Box>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" gap="spacing.1" alignItems="center">
              <Text weight="bold" size="large" color="feedback.text.positive.lowContrast">
                Total recovered GMV:
              </Text>
              {GMV ? (
                <>
                  <Amount
                    value={parseFloat(GMV)}
                    currency="INR"
                    intent="positive"
                    size="heading-small-bold"
                    isAffixSubtle={false}
                  />{' '}
                  🎉`
                </>
              ) : null}
            </Box>
          </ModalFooter>
        </Modal>
      </Box>
    </TriggerOnQueryParamMatch>
  );
};

export default FailedPaymentsRetry;

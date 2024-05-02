import React, { useState } from 'react';
import {
  Heading,
  Box,
  Card,
  CardHeader,
  CardHeaderLeading,
  CardBody,
  TextInput,
  Button,
  Text,
  SearchIcon,
  ChevronRightIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  XCircleIcon,
  Badge,
  Switch,
} from '@razorpay/blade/components';

import BankPlaceholder from 'assets/assisted_financing/bank_placeholder.png';
import { isMobileDevice } from 'merchant/components/Home/data';

import { paymentLinkBannerTitles } from './constants';
import { PaymentLinkBanner, PaymentMethodContainer, StyledImage } from './styled';
import { METHODS, AvailableEmiPropType } from './type';

const TableHeading = ({ title, isMobile }) => {
  return (
    <Box
      backgroundColor="surface.background.gray.subtle"
      padding={['spacing.3', 'spacing.4']}
      borderBottomWidth="thick"
      borderBottomColor="surface.border.gray.muted"
    >
      <Text weight="semibold" size={isMobile ? 'small' : 'medium'} truncateAfterLines={1}>
        {title}
      </Text>
    </Box>
  );
};

const TableCell = ({ value, isMobile }) => {
  return (
    <Box
      padding={['spacing.3', 'spacing.3']}
      borderBottomWidth="thick"
      borderBottomColor="surface.border.gray.muted"
    >
      <Text size={isMobile ? 'small' : 'medium'} truncateAfterLines={1}>
        {value}
      </Text>
    </Box>
  );
};

const PaymentLinkComponent = ({ paymentLinkData, showPaymentLinkModal, isMobile }) => {
  return (
    <Box
      display="flex"
      gap="spacing.3"
      padding={['spacing.7', 'spacing.5', 'spacing.4']}
      flex="1"
      flexDirection="column"
      borderWidth="thinner"
      borderColor="surface.border.gray.subtle"
    >
      {paymentLinkData.method === METHODS.CARDLESS_EMI ? (
        <PaymentLinkBanner>
          <Text weight="semibold">
            Share the payment link with the customer to initiate the payment.
          </Text>
          <Box display="flex" flexDirection="column" gap="spacing.3" marginTop="spacing.4">
            <Text>With the payment link, customer will be able to check:</Text>
            {paymentLinkBannerTitles.map((title, index) => (
              <Box
                key={index}
                display="flex"
                gap="spacing.2"
                alignItems="center"
                padding={['spacing.2', 'spacing.0', 'spacing.0', 'spacing.3']}
                flex="1"
              >
                <CheckIcon size="medium" color="feedback.icon.positive.intense" />
                <Text size="small">{title}</Text>
              </Box>
            ))}
          </Box>
        </PaymentLinkBanner>
      ) : (
        <Box overflow="scroll">
          <table style={{ width: '100%' }}>
            <thead>
              <tr>
                <td>
                  <TableHeading title="EMI Plan" isMobile={isMobile} />
                </td>
                <td>
                  <TableHeading title="Interest (pa)" isMobile={isMobile} />
                </td>
                <td>
                  <TableHeading title="Total Cost" isMobile={isMobile} />
                </td>
              </tr>
            </thead>
            <tbody>
              {paymentLinkData?.emiPlan.map((emiPlan, index) => (
                <tr key={index}>
                  <td>
                    <TableCell value={emiPlan.emiPlan} isMobile={isMobile} />
                  </td>
                  <td>
                    <TableCell value={emiPlan.interest} isMobile={isMobile} />
                  </td>
                  <td>
                    <TableCell value={emiPlan.totalPayable} isMobile={isMobile} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </Box>
      )}

      <Box alignSelf="flex-end" marginTop="spacing.5">
        <Button
          iconPosition="left"
          onClick={showPaymentLinkModal}
          size="medium"
          type="button"
          variant="primary"
        >
          Send payment link
        </Button>
      </Box>
    </Box>
  );
};

const LenderListItem = ({ item, onItemClick, isSelected }) => {
  const { title, image, notEligible } = item;
  const Icon = isMobileDevice() ? (isSelected ? ChevronUpIcon : ChevronDownIcon) : ChevronRightIcon;

  return (
    <PaymentMethodContainer isSelected={isSelected} onClick={onItemClick}>
      <Box display="flex" gap="spacing.3" width="336px">
        <StyledImage
          width="20px"
          height="20px"
          src={image || BankPlaceholder}
          alt="Provider Image"
          notEligible={notEligible}
        />
        <Box
          display="flex"
          flexDirection="column"
          flexWrap="wrap"
          gap="spacing.2"
          marginLeft="spacing.4"
        >
          <Text
            size={isMobileDevice() ? 'medium' : 'large'}
            weight="semibold"
            color={notEligible ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
            truncateAfterLines={2}
          >
            {title}
          </Text>
        </Box>
      </Box>
      {notEligible ? (
        <Box minWidth="85px">
          <Badge color="neutral" size="large">
            Not Eligible
          </Badge>
        </Box>
      ) : (
        <Icon
          size="medium"
          color={isSelected ? 'interactive.icon.primary.subtle' : 'interactive.icon.gray.normal'}
        />
      )}
    </PaymentMethodContainer>
  );
};

const AvailableEmiOption = ({
  merchantPaymentMethods,
  showPaymentLinkModal,
  setPaymentLinkData,
  paymentLinkData,
  shouldShowEmiMethods,
}: AvailableEmiPropType): JSX.Element | null => {
  const [filter, setFilter] = React.useState('');
  const [isSelectedIndex, setIsSelectedIndex] = React.useState<null | number>(null);
  const hasCardlessEmi = merchantPaymentMethods.some(
    (item) => item.method === METHODS.CARDLESS_EMI,
  );
  const [isShowCardlessEmi, setIsShowCardlessEmi] = useState(false);
  const isMobile = isMobileDevice();

  if (!shouldShowEmiMethods) {
    return null;
  }

  return (
    <Card backgroundColor="surface.background.gray.moderate" width="100%">
      <CardHeader>
        <CardHeaderLeading title="Available EMI Options" />
      </CardHeader>
      <CardBody>
        <Box
          display="flex"
          height={isMobileDevice() ? '680px' : '340px'}
          flexDirection={isMobileDevice() ? 'column' : 'row'}
        >
          {merchantPaymentMethods.length === 0 ? (
            <Box
              display="flex"
              flex={1}
              alignItems="center"
              justifyContent="center"
              flexDirection="column"
            >
              <XCircleIcon size="2xlarge" color="feedback.icon.negative.intense" />
              <Heading marginTop="spacing.4" marginBottom="spacing.3">
                No available EMI options
              </Heading>
              <Box width="40%">
                <Text size="small" textAlign="center">
                  There are no EMI options available for this customer currently. Kindly check back
                  later or try with a different mobile number.
                </Text>
              </Box>
            </Box>
          ) : (
            <>
              <Box
                display="flex"
                flexDirection="column"
                flex={1}
                maxWidth={isMobileDevice() ? 'auto' : '50%'}
                overflow="scroll"
              >
                <TextInput
                  icon={SearchIcon}
                  accessibilityLabel="Search EMI options"
                  placeholder="Search EMI options"
                  name="SearchEMIOptions"
                  validationState="none"
                  value={filter}
                  onChange={(e) => {
                    setFilter(e.value as string);
                  }}
                  position="sticky"
                />
                {hasCardlessEmi && (
                  <Box
                    display="flex"
                    flexDirection="row"
                    marginX="spacing.3"
                    marginY="spacing.5"
                    justifyContent="space-between"
                    alignItems="center"
                  >
                    <Text weight="semibold" color="surface.text.gray.muted">
                      Show only Cardless EMI
                    </Text>
                    <Switch
                      onChange={(e) => {
                        setIsShowCardlessEmi(e.isChecked);
                      }}
                      accessibilityLabel="show cardless emi"
                    />
                  </Box>
                )}
                <Box flex={1} overflow="scroll">
                  {isMobileDevice()
                    ? merchantPaymentMethods
                        .filter((item) =>
                          isShowCardlessEmi ? item.method === METHODS.CARDLESS_EMI : true,
                        )
                        .filter((f) => f.title.toLowerCase().includes(filter) || filter === '')
                        .map((item, index) => {
                          return (
                            <Box
                              display="flex"
                              flexDirection="column"
                              borderWidth="thinner"
                              borderColor={
                                isSelectedIndex === index
                                  ? 'surface.border.primary.normal'
                                  : 'surface.border.gray.muted'
                              }
                              key={index}
                            >
                              <LenderListItem
                                item={item}
                                isSelected={isSelectedIndex === index}
                                onItemClick={() => {
                                  if (item.notEligible) return;
                                  setPaymentLinkData(item);
                                  setIsSelectedIndex(index === isSelectedIndex ? null : index);
                                }}
                              />
                              {isSelectedIndex === index && (
                                <Box>
                                  {item.method === 'emi' && paymentLinkData?.emiPlan && (
                                    <Box overflow="scroll">
                                      <table style={{ width: '100%' }}>
                                        <thead>
                                          <tr>
                                            <td>
                                              <TableHeading title="EMI Plan" isMobile={isMobile} />
                                            </td>
                                            <td>
                                              <TableHeading
                                                title="Interest (pa)"
                                                isMobile={isMobile}
                                              />
                                            </td>
                                            <td>
                                              <TableHeading
                                                title="Total Cost"
                                                isMobile={isMobile}
                                              />
                                            </td>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          {paymentLinkData?.emiPlan.map((emiPlan, index) => (
                                            <tr key={index}>
                                              <td>
                                                <TableCell
                                                  value={emiPlan.emiPlan}
                                                  isMobile={isMobile}
                                                />
                                              </td>
                                              <td>
                                                <TableCell
                                                  value={emiPlan.interest}
                                                  isMobile={isMobile}
                                                />
                                              </td>
                                              <td>
                                                <TableCell
                                                  value={emiPlan.totalPayable}
                                                  isMobile={isMobile}
                                                />
                                              </td>
                                            </tr>
                                          ))}
                                        </tbody>
                                      </table>
                                    </Box>
                                  )}
                                  <Box padding="spacing.4">
                                    <Button
                                      iconPosition="left"
                                      onClick={showPaymentLinkModal}
                                      size="medium"
                                      type="button"
                                      variant="primary"
                                      isFullWidth={true}
                                    >
                                      Send payment link
                                    </Button>
                                  </Box>
                                </Box>
                              )}
                            </Box>
                          );
                        })
                    : merchantPaymentMethods
                        .filter((item) =>
                          isShowCardlessEmi ? item.method === METHODS.CARDLESS_EMI : true,
                        )
                        .filter((f) => f.title.toLowerCase().includes(filter) || filter === '')
                        .map((item) => {
                          return (
                            <LenderListItem
                              key={item.title}
                              item={item}
                              isSelected={paymentLinkData?.title === item.title}
                              onItemClick={() => {
                                if (item.notEligible) return;
                                setPaymentLinkData(item);
                              }}
                            />
                          );
                        })}
                </Box>
              </Box>
              {paymentLinkData && !isMobileDevice() && (
                <PaymentLinkComponent
                  paymentLinkData={paymentLinkData}
                  showPaymentLinkModal={showPaymentLinkModal}
                  isMobile={isMobile}
                />
              )}
              <Box flex={isMobileDevice() ? 0 : 0.2} />
            </>
          )}
        </Box>
      </CardBody>
    </Card>
  );
};

export default AvailableEmiOption;

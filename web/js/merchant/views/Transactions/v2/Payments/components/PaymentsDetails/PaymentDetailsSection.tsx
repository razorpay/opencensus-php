import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  Heading,
  Link,
  MailIcon,
  PhoneIcon,
  Text,
} from '@razorpay/blade/components';
import { RouteComponentProps, withRouter } from 'react-router-dom';

import { useMobile } from 'common/hooks/useMobile';
import copyToClipboard from 'common/utils/copyToClipboard';

import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import Tooltip from './Tooltip';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import { IPaymentDetails } from './types';
import { onCopy } from './utils';

interface IPaymentDetailsSection extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
}

function PaymentDetailsSection({
  paymentDetails,
  history,
  location,
  match: {
    params: { id: transactionIDActual },
  },
}: IPaymentDetailsSection): React.ReactElement {
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const toggleAccordian = () => {
    setIsOpen((prevState) => !prevState);
  };
  const isMobile = useMobile();
  const isStorefront = location.hash === '#storefront';

  useEffect(() => {
    // if device type changes to desktop, ensure isOpen is reset to true
    if (!isMobile) {
      setIsOpen(true);
    }
  }, [isMobile]);
  const {
    id,
    acquirer_data,
    notes,
    order_id,
    card,
    contact,
    email,
    disputes,
    fee_bearer,
    description,
    method,
    bank,
    vpa,
    wallet,
    invoice_id,
  } = paymentDetails;
  return (
    <Box testID="payment-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Heading type="normal" size="small" weight="bold" contrast="low">
          Details
        </Heading>
        {isMobile && (
          <CollapsibleContainer onClick={toggleAccordian} data-testid="collapsible-container">
            <Text type="subtle" size="medium" weight="bold">
              {!isOpen ? (
                <ChevronDownIcon
                  size="medium"
                  color="feedback.icon.neutral.lowContrast"
                  data-testid="chevron-down"
                />
              ) : (
                <ChevronUpIcon
                  size="medium"
                  color="feedback.icon.neutral.lowContrast"
                  data-testid="chevron-up"
                />
              )}
            </Text>
          </CollapsibleContainer>
        )}
      </SectionHeader>
      {isOpen ? (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Payment ID <Tooltip type="paymentId" size="small" />
                  </Text>
                  <CopyWrapper
                    onClick={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                      null,
                      id,
                    )}
                  >
                    <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                      {id}
                    </Text>
                  </CopyWrapper>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Bank RRN <Tooltip type="bankRRN" size="small" />
                  </Text>
                  <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                    {acquirer_data.rrn || '--'}
                  </Text>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Order ID <Tooltip type="orderId" size="small" />
                  </Text>
                  {order_id ? (
                    <CopyWrapper
                      onClick={onCopy('Order ID', { transactionIDActual, orderId: order_id }).bind(
                        null,
                        order_id,
                      )}
                    >
                      <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                        {order_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Invoice ID
                  </Text>
                  {invoice_id ? (
                    <CopyWrapper onClick={copyToClipboard.bind(null, order_id)}>
                      <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                        {invoice_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Payment method
                  </Text>
                  <PaymentMethod
                    payment={paymentDetails}
                    method={method}
                    card={card}
                    bank={bank}
                    vpa={vpa}
                    wallet={wallet}
                  />
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Customer details
                  </Text>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    {notes.name && (
                      <Text
                        type="normal"
                        variant="body"
                        size="medium"
                        weight="regular"
                        contrast="low"
                      >
                        {notes.name}
                      </Text>
                    )}
                    {contact && (
                      <Box display="inline-flex" gap="spacing.3" alignItems="center">
                        <PhoneIcon size="medium" color="surface.text.subtle.lowContrast" />
                        <Text
                          type="normal"
                          variant="body"
                          size="medium"
                          weight="regular"
                          contrast="low"
                        >
                          {contact}
                        </Text>
                      </Box>
                    )}
                    {email && (
                      <Box display="inline-flex" gap="spacing.3" alignItems="center">
                        <MailIcon size="medium" color="surface.text.subtle.lowContrast" />
                        <Text
                          type="normal"
                          variant="body"
                          size="medium"
                          weight="regular"
                          contrast="low"
                        >
                          {email}
                        </Text>
                      </Box>
                    )}
                  </Box>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Fee bearer
                  </Text>
                  <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                    {fee_bearer === 'platform'
                      ? 'You pay the Razorpay platform fee'
                      : 'The customer has paid the fees for this payment'}
                  </Text>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Description
                  </Text>
                  <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                    {description || `--`}
                  </Text>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Notes
                  </Text>
                  {getNotes({ notes, isStorefront })}
                </RowWrapper>
                {/* Show only if dispute is raised */}
                {disputes.items.length > 0 && (
                  <>
                    <Divider
                      contrast="low"
                      dividerStyle="solid"
                      thickness="thick"
                      variant="normal"
                    />
                    <RowWrapper>
                      <Text
                        type="subtle"
                        variant="body"
                        size="medium"
                        weight="regular"
                        contrast="low"
                      >
                        Dispute ID
                      </Text>
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        {disputes.items.map((item) => (
                          <Link key={item.id} onClick={() => history.push(`/disputes/${item.id}`)}>
                            {item.id}
                          </Link>
                        ))}
                      </Box>
                    </RowWrapper>
                  </>
                )}
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
}

export default withRouter(PaymentDetailsSection);

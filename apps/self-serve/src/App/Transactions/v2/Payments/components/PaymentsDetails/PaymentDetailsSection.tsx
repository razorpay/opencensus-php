import { useMobile } from '@dashboard/shared-ui/hooks';
import copyToClipboard from '@dashboard/shared-utils/copyToClipboard';
import { getI18FormattedPhoneNumber } from '@dashboard/shared-utils/i18/contact';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  Link,
  MailIcon,
  PhoneIcon,
  Text,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { withRouter } from 'shell/deprecated/withRouter';

import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import PaymentTransfers from './PaymentTransfers';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import Tooltip from './Tooltip';
import { ApplicationDetails, IPaymentDetails } from './types';
import { onCopy } from './utils';

interface IPaymentDetailsSection extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
  applicationDetails: ApplicationDetails | null;
}

function PaymentDetailsSection({
  paymentDetails,
  applicationDetails,
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
    acquirer_data = {},
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
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
        {isMobile ? (
          <CollapsibleContainer onClick={toggleAccordian} data-testid="collapsible-container">
            <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
              {!isOpen ? (
                <ChevronDownIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-down"
                />
              ) : (
                <ChevronUpIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-up"
                />
              )}
            </Text>
          </CollapsibleContainer>
        ) : null}
      </SectionHeader>
      {isOpen ? (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Payment ID <Tooltip size="small" />
                  </Text>
                  <CopyWrapper
                    onClick={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                      null,
                      id,
                    )}
                  >
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      {id}
                    </Text>
                  </CopyWrapper>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Bank RRN <Tooltip size="small" />
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {acquirer_data.rrn || '--'}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Order ID <Tooltip size="small" />
                  </Text>
                  {order_id ? (
                    <CopyWrapper
                      onClick={onCopy('Order ID', { transactionIDActual, orderId: order_id }).bind(
                        null,
                        order_id,
                      )}
                    >
                      <Text
                        variant="body"
                        size="medium"
                        weight="semibold"
                        color="surface.text.gray.normal"
                      >
                        {order_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Invoice ID
                  </Text>
                  {invoice_id ? (
                    <CopyWrapper onClick={copyToClipboard.bind(null, invoice_id)}>
                      <Text
                        variant="body"
                        size="medium"
                        weight="semibold"
                        color="surface.text.gray.normal"
                      >
                        {invoice_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
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
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Customer details
                  </Text>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    {notes.name ? (
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.normal"
                      >
                        {notes.name}
                      </Text>
                    ) : null}
                    {contact ? (
                      <Box display="inline-flex" gap="spacing.3" alignItems="center">
                        <PhoneIcon size="medium" color="interactive.icon.gray.subtle" />
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          {getI18FormattedPhoneNumber(contact)}
                        </Text>
                      </Box>
                    ) : null}
                    {email ? (
                      <Box display="inline-flex" gap="spacing.3" alignItems="center">
                        <MailIcon size="medium" color="interactive.icon.gray.subtle" />
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          {email}
                        </Text>
                      </Box>
                    ) : null}
                    {/* if nothing exists, show -- */}
                    {!notes.name && !contact && !email ? (
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.normal"
                      >
                        --
                      </Text>
                    ) : null}
                  </Box>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Fee bearer
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {fee_bearer === 'platform'
                      ? 'You pay the Razorpay platform fee'
                      : 'The customer has paid the fees for this payment'}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    App Name
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {applicationDetails?.name || `--`}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    App ID
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {applicationDetails?.id || `--`}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Description
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {description || `--`}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Notes
                  </Text>
                  {getNotes({ notes, isStorefront })}
                </RowWrapper>
                <PaymentTransfers paymentDetails={paymentDetails} />
                {/* Show only if dispute is raised */}
                {disputes.items.length > 0 ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <RowWrapper>
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.subtle"
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
                ) : null}
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
}

export default withRouter(PaymentDetailsSection);

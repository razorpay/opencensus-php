import React from 'react';
import {
  Modal,
  Text,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Box,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Link,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { SETTLEMENTS_GUIDE_MODAL_DATA } from '@FTUX/constants/payments';

const SettlementsGuideModal = ({ onDismiss }: { onDismiss: () => void }) => {
  const isMobile = isMobileDevice();

  return (
    <Modal size={isMobile ? 'small' : 'medium'} onDismiss={onDismiss} isOpen={true}>
      <ModalHeader title="Settlements Guide" />
      <ModalBody>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.7"
          alignSelf="strech"
        >
          <Box
            display="flex"
            flexDirection="column"
            alignItems="flex-start"
            gap="spacing.3"
            alignSelf="strech"
          >
            <Text>
              Settlements are how payments collected from your customers are deposited in your bank
              account.
            </Text>
            <Text>
              Settlements are processed within{' '}
              <Text as="span" variant="body" weight="semibold">
                T+2 working days*
              </Text>{' '}
              for domestic payments, and within{' '}
              <Text as="span" variant="body" weight="semibold">
                T+2 working days*
              </Text>{' '}
              for international payments.
            </Text>
            <Text color="surface.text.gray.muted">
              *T being the day of payment collection. Working days exclude public and bank holidays
            </Text>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="flex-start">
            <Text weight="semibold" size="small">
              Here’s an example of a domestic settlement:
            </Text>

            <Table data={SETTLEMENTS_GUIDE_MODAL_DATA} showBorderedCells>
              {(data) => (
                <>
                  <TableHeader>
                    <TableHeaderRow>
                      <TableHeaderCell>Date</TableHeaderCell>
                      <TableHeaderCell>Days since transaction</TableHeaderCell>
                      <TableHeaderCell>Transaction status</TableHeaderCell>
                    </TableHeaderRow>
                  </TableHeader>
                  <TableBody>
                    {data.map((tableItem, index) => (
                      <TableRow key={index} item={tableItem}>
                        <TableCell>{tableItem.date}</TableCell>
                        <TableCell>{tableItem.period}</TableCell>
                        <TableCell>{tableItem.status}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              )}
            </Table>
            <Text size="xsmall" color="surface.text.gray.muted">
              <Text
                as="span"
                variant="body"
                weight="semibold"
                size="xsmall"
                color="surface.text.gray.muted"
              >
                Note:
              </Text>{' '}
              Your final settlement amount will vary after adjusting for platform fees, taxes,
              refunds, credits, or any other charges.
            </Text>
            <Link icon={ExternalLinkIcon} iconPosition="right">
              View complete guide
            </Link>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button onClick={onDismiss} isFullWidth>
            Okay, got it
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default SettlementsGuideModal;

import React from 'react';
import {
  Text,
  Button,
  Box,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { SETTLEMENTS_GUIDE_MODAL_DATA, SETTLEMENTS_GUIDE_URL } from '@FTUX/constants/payments';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

const SettlementsGuideModal = ({ onDismiss }: { onDismiss: () => void }) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  return (
    <Modal
      snapPoints={[0.8, 0.8, 0.8]}
      size={isMobile ? 'small' : 'medium'}
      onDismiss={onDismiss}
      isOpen={true}
      zIndex={zIndicesMap.modal}
    >
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
                      <TableHeaderCell>
                        <Box padding="spacing.2" whiteSpace="normal">
                          <Text weight="medium">Days since transaction</Text>
                        </Box>
                      </TableHeaderCell>
                      <TableHeaderCell>
                        <Box padding="spacing.2" whiteSpace="normal">
                          <Text weight="medium">Transaction status</Text>
                        </Box>
                      </TableHeaderCell>
                    </TableHeaderRow>
                  </TableHeader>
                  <TableBody>
                    {data.map((tableItem, index) => (
                      <TableRow key={index} item={tableItem}>
                        <TableCell>{tableItem.date}</TableCell>
                        <TableCell>{tableItem.period}</TableCell>
                        <TableCell>
                          <Box padding="spacing.2" whiteSpace="normal">
                            <Text>{tableItem.status}</Text>
                          </Box>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              )}
            </Table>
            <Text size={isMobile ? 'xsmall' : 'small'} color="surface.text.gray.muted">
              <Text
                as="span"
                variant="body"
                weight="semibold"
                size={isMobile ? 'xsmall' : 'small'}
                color="surface.text.gray.muted"
              >
                Note:
              </Text>{' '}
              Your final settlement amount will vary after adjusting for platform fees, taxes,
              refunds, credits, or any other charges.
            </Text>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box
          display="flex"
          flexDirection={{ base: 'column-reverse', m: 'row' }}
          gap={{ base: 'spacing.5', m: 'spacing.3' }}
          justifyContent="flex-end"
          width="100%"
        >
          <Button
            onClick={onDismiss}
            variant="tertiary"
            isFullWidth={!!isMobile}
            href={SETTLEMENTS_GUIDE_URL}
            target="_blank"
            data-analytics-name="view-complete-guide"
          >
            View complete guide
          </Button>
          <Button
            onClick={onDismiss}
            isFullWidth={!!isMobile}
            data-analytics-name="got-settlement-guide"
          >
            Got it
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default SettlementsGuideModal;

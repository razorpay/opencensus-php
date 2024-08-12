import React, { useState, useEffect } from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Checkbox,
  CheckboxGroup,
  UsersIcon,
  Card,
  CardBody,
  Text,
  CheckCircleIcon,
  FileTextIcon,
  Badge,
} from '@razorpay/blade/components';
import moment from 'moment';
import styled from 'styled-components';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';

const SuccessBox = styled.div(
  ({ theme }) => `
  background-color: #EDF7F7; //theme.colors.surface.background.sea.subtle
  padding: ${theme.spacing[4]}px;
`,
);

export default function ExportReportModal({ isOpen, setIsOpen, filters, merchantProcessId }) {
  const [step, setStep] = useState(1);
  const [selectedEmails, setSelectedEmails] = useState([]);
  const [emails, setEmails] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  const submitReportRequest = async () => {
    setIsLoading(true);
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessTransactionView,
      objectName: 'recon export view',
      actionName: 'click',
      properties: {
        processId: merchantProcessId,
        emails: selectedEmails.join(','),
      },
    });
    const obj = {
      merchant_process_id: merchantProcessId,
      filters: [],
      from_date: filters?.startEndDates?.startDate,
      to_date: filters?.startEndDates?.endDate,
      emails: selectedEmails,
    };
    const res = await merchantFetch({
      url: `recon-saas/recon_process/export_records`,
      mode: 'live',
      method: 'POST',
      data: obj,
    });
    if (res?.status_code === 200) {
      setStep(2);
    }
    setIsLoading(false);
  };

  const handleShare = () => {
    if (step === 1) {
      submitReportRequest();
    } else {
      setStep(1);
      setIsOpen(false);
    }
  };

  const closeModal = () => {
    setStep(1);
    setIsOpen(false);
  };

  const fetchEmails = async () => {
    const res = await merchantFetch({
      url: `recon-saas/merchant_config/emails`,
      mode: 'live',
      method: 'get',
    });
    if (res?.status_code === 200) {
      setEmails(res.data);
    }
  };

  useEffect(() => {
    fetchEmails();
  }, []);

  useReconTracking({
    objectName: 'recon export view',
    screen: ReconScreens.ProcessTransactionView,
    properties: {
      processId: merchantProcessId,
    },
  });

  const from = moment(filters?.startEndDates?.startDate * 1000).format('DD MMM YYYY');
  const to = moment(filters?.startEndDates?.endDate * 1000).format('DD MMM YYYY');

  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="small">
      <ModalHeader title="Export View" subtitle="Get a CSV file through email" />
      <ModalBody>
        <Card padding="spacing.4" marginBottom="spacing.6" elevation="none">
          <CardBody>
            <Box display="flex">
              <FileTextIcon marginRight="spacing.2" marginTop="spacing.2" />
              <Box>
                <Text weight="semibold" color="surface.text.gray.subtle">
                  Reconciliation report
                </Text>
                <Box display="flex" alignItems="center">
                  <Text color="surface.text.gray.muted">
                    {from} to {to}
                  </Text>
                  {filters?.type ? (
                    <Badge size="small" marginLeft="spacing.2">
                      <Text weight="semibold" color="surface.text.gray.subtle">
                        {filters?.type}
                      </Text>
                    </Badge>
                  ) : null}
                </Box>
              </Box>
            </Box>
          </CardBody>
        </Card>
        {step === 1 ? (
          <>
            <Box display="flex" alignItems="center" marginBottom="spacing.4">
              <UsersIcon />
              <Text marginLeft="spacing.2" weight="semibold">
                Sharing With
              </Text>
            </Box>
            <CheckboxGroup onChange={({ values }) => setSelectedEmails(values)}>
              {Array.isArray(emails) &&
                emails.map((email) => (
                  <Checkbox value={email} key={email}>
                    {email}
                  </Checkbox>
                ))}
            </CheckboxGroup>
          </>
        ) : null}
        {step === 2 ? (
          <SuccessBox>
            <CheckCircleIcon size="2xlarge" color="surface.text.onSea.onSubtle" />
            <Text size="large" color="surface.text.gray.subtle">
              Generating report
            </Text>
            <Text color="surface.text.gray.subtle">
              We&apos;ve received your request for report. It&apos;ll take a few minutes to generate
              and you&apos;ll receive an email shortly with the report.
            </Text>
          </SuccessBox>
        ) : null}
      </ModalBody>
      <ModalFooter>
        <Box width="100%">
          <Button
            onClick={handleShare}
            isFullWidth
            isDisabled={step === 1 && selectedEmails.length === 0}
            isLoading={isLoading}
          >
            {step === 1 ? 'Share' : 'Close'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import DateTimeCell from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable/Cells/DateTimeCell';
import { TitleCellValue } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';
import {
  getStatusName,
  getReportDeliveryTimeDifference,
} from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable/utils';

const TRIGGERED = 'Triggered';
const INVOICE_DATE_TIME = 'Invoice Date & Time';
const UPLOAD_DATE_TIME = 'Upload Date & Time';
const API_CALL_TIME = 'API Call Date & Time';
const DELIVERY = 'Delivery';
const SPEED_UPLOAD_TO_DELIVERY = 'Speed (Upload to delivery)';
const SPEED_SMS_TO_DELIVERY = 'Speed (SMS API to delivery)';
const SPEED_GENERATION_TO_DELIVERY = 'Speed (Generation to delivery)';

// TODO: Update properties consumption after API integration
export const TITLES = {
  [TRIGGERED]: {
    cellHeader: TRIGGERED,
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        smsDeliveryReport ? 'Yes' : 'No',
        emailDeliveryReport ? 'Yes' : 'No',
        whatsAppDeliveryReport ? 'Yes' : 'No',
      ];
    },
  },
  [INVOICE_DATE_TIME]: {
    cellHeader: INVOICE_DATE_TIME,
    cellValue: ({ timestamp }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        <DateTimeCell key={`${timestamp}-0`} timestamp={timestamp ?? null} />,
        <DateTimeCell key={`${timestamp}-1`} timestamp={timestamp ?? null} />,
        <DateTimeCell key={`${timestamp}-2`} timestamp={timestamp ?? null} />,
      ];
    },
  },

  [UPLOAD_DATE_TIME]: {
    cellHeader: UPLOAD_DATE_TIME,
    cellValue: ({ timestamp }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        <DateTimeCell key={`${timestamp}-3`} timestamp={timestamp ?? null} />,
        <DateTimeCell key={`${timestamp}-4`} timestamp={timestamp ?? null} />,
        <DateTimeCell key={`${timestamp}-5`} timestamp={timestamp ?? null} />,
      ];
    },
  },
  [API_CALL_TIME]: {
    cellHeader: API_CALL_TIME,
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        <DateTimeCell
          key={`${smsDeliveryReport?.createdAt}-6`}
          timestamp={smsDeliveryReport?.createdAt ?? null}
        />,
        <DateTimeCell
          key={`${emailDeliveryReport?.createdAt}-7`}
          timestamp={emailDeliveryReport?.createdAt ?? null}
        />,
        <DateTimeCell
          key={`${whatsAppDeliveryReport?.createdAt}-8`}
          timestamp={whatsAppDeliveryReport?.createdAt ?? null}
        />,
      ];
    },
  },
  [DELIVERY]: {
    cellHeader: DELIVERY,
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        getStatusName(smsDeliveryReport),
        getStatusName(emailDeliveryReport),
        getStatusName(whatsAppDeliveryReport),
      ];
    },
  },
  [SPEED_UPLOAD_TO_DELIVERY]: {
    cellHeader: (
      <Box>
        <Text>Speed</Text>
        <Text size="small" color="surface.text.gray.muted">
          (Upload to delivery)
        </Text>
      </Box>
    ),
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
      timestamp,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        getReportDeliveryTimeDifference(
          smsDeliveryReport?.status,
          smsDeliveryReport?.deliveredAt,
          timestamp,
        ),
        getReportDeliveryTimeDifference(
          emailDeliveryReport?.status,
          emailDeliveryReport?.deliveredAt,
          timestamp,
        ),
        getReportDeliveryTimeDifference(
          whatsAppDeliveryReport?.status,
          whatsAppDeliveryReport?.deliveredAt,
          timestamp,
        ),
      ];
    },
  },
  [SPEED_SMS_TO_DELIVERY]: {
    cellHeader: (
      <Box>
        <Text>Speed</Text>
        <Text size="small" color="surface.text.gray.muted">
          (SMS API to delivery)
        </Text>
      </Box>
    ),
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        getReportDeliveryTimeDifference(
          smsDeliveryReport?.status,
          smsDeliveryReport?.deliveredAt,
          smsDeliveryReport?.createdAt,
        ),
        getReportDeliveryTimeDifference(
          emailDeliveryReport?.status,
          emailDeliveryReport?.deliveredAt,
          emailDeliveryReport?.createdAt,
        ),
        getReportDeliveryTimeDifference(
          whatsAppDeliveryReport?.status,
          whatsAppDeliveryReport?.deliveredAt,
          whatsAppDeliveryReport?.createdAt,
        ),
      ];
    },
  },
  [SPEED_GENERATION_TO_DELIVERY]: {
    cellHeader: (
      <Box>
        <Text>Speed</Text>
        <Text size="small" color="surface.text.gray.muted">
          (Generation to delivery)
        </Text>
      </Box>
    ),
    cellValue: ({
      smsDeliveryReport,
      emailDeliveryReport,
      whatsAppDeliveryReport,
      timestamp,
    }: TitleCellValue): (React.ReactElement | string)[] => {
      return [
        getReportDeliveryTimeDifference(
          smsDeliveryReport?.status,
          smsDeliveryReport?.deliveredAt,
          timestamp,
        ),
        getReportDeliveryTimeDifference(
          emailDeliveryReport?.status,
          emailDeliveryReport?.deliveredAt,
          timestamp,
        ),
        getReportDeliveryTimeDifference(
          whatsAppDeliveryReport?.status,
          whatsAppDeliveryReport?.deliveredAt,
          timestamp,
        ),
      ];
    },
  },
} as const;

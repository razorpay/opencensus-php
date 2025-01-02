import React from 'react';
import { AlertProps, Text } from '@razorpay/blade/components';

import { User } from 'common/typings';

import { Strong } from './styled';
import { getParamsFromUser, onClickRedirectNC, onClickRedirectVKYC } from './utils';

const REKYC_DEADLINE = '31st Jan, 2025';
const REKYC_DEADLINE_SHORT = '31st Jan';

export const REKYC_STATUS_OPTIONS = {
  UNDER_REVIEW: 'under_review',
  NEEDS_CLARIFICATION: 'needs_clarification',
  EDD_PENDING: 'edd_pending',
  VKYC_UNDER_REVIEW: 'vkyc_under_review',
  APPROVED: 'approved',
  REJECTED: 'rejected',
};

type ModalContent = {
  title: string;
  description: React.ReactChild;
  action?: { text: string; onClick: (...props) => Promise<void> | void };
};

export const getModalContent = (user: User): ModalContent | null => {
  const { status, canPerformActions, ncCount } = getParamsFromUser(user);

  switch (status) {
    case REKYC_STATUS_OPTIONS.NEEDS_CLARIFICATION:
      if (ncCount > 1) {
        return {
          title: `Update your KYC by ${REKYC_DEADLINE_SHORT}`,
          description: canPerformActions ? (
            <>
              One or more documents or details that you provided seem to be incorrect. Please make
              the required changes before <Strong>{REKYC_DEADLINE}</Strong> to complete your KYC
              process.
            </>
          ) : (
            <>
              KYC needs to be updated before <Strong>{REKYC_DEADLINE}</Strong>. Please request the{' '}
              <Strong>account owner or admins</Strong> to update this to ensure smooth operations.
            </>
          ),
          action: {
            text: 'Resolve',
            onClick: onClickRedirectNC,
          },
        };
      }
      return {
        title: `Update your KYC by ${REKYC_DEADLINE_SHORT}`,
        description: canPerformActions ? (
          <>
            RBI requires every Razorpay merchant to undergo KYC. Please update your KYC by{' '}
            <Strong>{REKYC_DEADLINE}</Strong> to maintain smooth operations.
          </>
        ) : (
          <>
            KYC needs to be updated before <Strong>{REKYC_DEADLINE}</Strong>. Please request the{' '}
            <Strong>account owner or admins</Strong> to update this to ensure smooth operations.
          </>
        ),
        action: {
          text: 'Update KYC',
          onClick: onClickRedirectNC,
        },
      };
    case REKYC_STATUS_OPTIONS.EDD_PENDING:
      return {
        title: `Update Video KYC by ${REKYC_DEADLINE_SHORT}`,
        description: (
          <>
            Update Video KYC before <Strong>{REKYC_DEADLINE}</Strong>. Please request the{' '}
            <Strong>authorized signatory</Strong> to do the Video KYC to ensure smooth operations.
          </>
        ),
        action: {
          text: 'Update Video KYC',
          onClick: onClickRedirectVKYC,
        },
      };
    case REKYC_STATUS_OPTIONS.REJECTED:
      return {
        title: 'Your KYC is Rejected',
        description: (
          <>
            The KYC for this account has been <Strong>rejected</Strong>. We'll contact you shortly
            with further information.
          </>
        ),
      };
    default:
      return null;
  }
};

type BannerContent = {
  description: React.ReactChild;
  importance: AlertProps['color'];
  action?: { text: string; onClick: (...props) => void };
  dismissible?: boolean;
};

export const getBannerContent = (user: User): BannerContent | null => {
  const { status, canPerformActions, ncCount } = getParamsFromUser(user);

  switch (status) {
    case REKYC_STATUS_OPTIONS.UNDER_REVIEW:
      return {
        description: (
          <Text>
            <Strong>Your KYC is Under Review.</Strong> We'll notify you within 1-3 business days
            with a status update, or if we need any other details.
          </Text>
        ),
        importance: 'information',
      };
    case REKYC_STATUS_OPTIONS.NEEDS_CLARIFICATION:
      if (ncCount > 1) {
        return {
          description: canPerformActions ? (
            <Text>
              <Strong>Changes Requested!</Strong> Some of the documents you submitted for your KYC
              are invalid or incorrect. Please make the required changes.
            </Text>
          ) : (
            <Text>
              <Strong>Changes Requested!</Strong> Some of the documents submitted for KYC are
              invalid or incorrect. Please request <Strong>account owner or admins</Strong> to make
              the required changes.
            </Text>
          ),
          importance: 'notice',
          action: {
            text: 'Resolve',
            onClick: onClickRedirectNC,
          },
        };
      }
      return {
        description: canPerformActions ? (
          <Text>
            <Strong>Important!</Strong> Update your KYC before <Strong>{REKYC_DEADLINE}</Strong> to
            maintain smooth operations.
          </Text>
        ) : (
          <Text>
            <Strong>Important!</Strong> KYC needs to be updated before{' '}
            <Strong>{REKYC_DEADLINE}</Strong>. Please request the{' '}
            <Strong>account owner or admins</Strong> to update this.
          </Text>
        ),
        importance: 'notice',
        action: {
          text: 'Update KYC',
          onClick: onClickRedirectNC,
        },
      };
    case REKYC_STATUS_OPTIONS.EDD_PENDING:
      return {
        description: (
          <Text>
            <Strong>Important!</Strong> Update Video KYC before <Strong>{REKYC_DEADLINE}</Strong>.
            Please request the <Strong>authorized signatory</Strong> to update this.
          </Text>
        ),
        importance: 'notice',
        action: {
          text: 'Update Video KYC',
          onClick: onClickRedirectVKYC,
        },
      };
    case REKYC_STATUS_OPTIONS.VKYC_UNDER_REVIEW:
      return {
        description: (
          <Text>
            <Strong>Video KYC is Under Review.</Strong> We'll notify you within 24 hours with a
            status update, or if we need any other details.
          </Text>
        ),
        importance: 'information',
      };
    case REKYC_STATUS_OPTIONS.REJECTED:
      return {
        description: (
          <Text>
            <Strong>Important!</Strong> Your KYC has been rejected. We'll contact you shortly with
            further information.
          </Text>
        ),
        importance: 'negative',
      };
    case REKYC_STATUS_OPTIONS.APPROVED:
      return {
        description: (
          <Text>
            <Strong>Great news!</Strong> Your Video KYC has been approved 🎉.
          </Text>
        ),
        importance: 'positive',
        dismissible: true,
      };
    default:
      return null;
  }
};

import { createAsyncThunk } from '@reduxjs/toolkit';

import {
  EDD_STATUS,
  MORE_PAYMENT_METHOD_STATUS,
  REDUCER_INITIAL_STATE,
  V_KYC_STATUS,
} from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { getKycDocumentStatus } from 'merchant/reducers/unlockIntlPaymentMethods/utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { ProductWorkflowStatesInBackend } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

export const fetchEddDetails = createAsyncThunk(
  'unlockIntlPaymentMethods/fetchEddDetails',
  async (workflowStatus: ProductWorkflowStatesInBackend) => {
    const requests = [
      merchantFetch({
        url: 'edd_details',
        mode: 'live',
      }),
    ];

    if (workflowStatus !== ProductWorkflowStatesInBackend.APPROVED) {
      requests.push(
        merchantFetch({
          url: `merchant/${WORKFLOW_TYPES.INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT}/details`,
          mode: 'live',
        }),
      );
    }

    const [{ success, data }, workflowResponse] = await Promise.all(requests);

    const workflowData = workflowResponse?.data;
    const { rejection_reason_message, workflow_exists, workflow_status } = workflowData ?? {};

    if (success && data) {
      const { status, details } = data;
      const vKycDetails = Array.isArray(details)
        ? details.find((item) => item.type === 'vkyc')
        : details;

      const kycDocumentStatus: REDUCER_INITIAL_STATE['kycDocumentStatus'] = getKycDocumentStatus({
        workflowStatus,
        workflowInfo: workflowData,
      });
      const eddStatus = status ?? EDD_STATUS.DEFAULT;
      const kycRejectedReason = rejection_reason_message;
      const vKycStatus = vKycDetails?.status ?? V_KYC_STATUS.DEFAULT;
      const vKycRejectedReason = vKycDetails?.reason?.toLowerCase() ?? '';

      const statusReturnObj = {
        eddStatus,
        kycDocumentStatus,
        kycRejectedReason,
        vKycStatus,
        vKycRejectedReason,
      };

      /**
       * if edd status is already verified and no workflow is created then we mark the overall status to verified
       * if both edd status and workflow status is verified then update the overall status to verified
       */
      if (
        (eddStatus === EDD_STATUS.VERIFIED && workflow_exists === false) ||
        (eddStatus === EDD_STATUS.VERIFIED &&
          workflowStatus === ProductWorkflowStatesInBackend.APPROVED)
      ) {
        return {
          ...statusReturnObj,
          status: MORE_PAYMENT_METHOD_STATUS.VERIFIED,
        };
      }

      /**
       * if edd status is rejected with no more retry then update the overall status to retry impossible
       */
      if (
        eddStatus === EDD_STATUS.REJECTED &&
        vKycRejectedReason === 'customer seems to be a fraud'
      ) {
        return {
          ...statusReturnObj,
          status: MORE_PAYMENT_METHOD_STATUS.RETRY_IMPOSSIBLE,
        };
      }

      /**
       * if workflow is not created then update kycDocumentStatus to initiated
       */
      if (
        workflowStatus === ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED &&
        workflow_exists === false
      ) {
        return {
          ...statusReturnObj,
          status: MORE_PAYMENT_METHOD_STATUS.INITIAL,
        };
      }

      /**
       * if workflow is rejected then mark kycDocumentStatus to rejected
       */
      if (
        workflowStatus === ProductWorkflowStatesInBackend.REJECTED ||
        workflow_status === ProductWorkflowStatesInBackend.REJECTED
      ) {
        return {
          ...statusReturnObj,
          status: MORE_PAYMENT_METHOD_STATUS.REJECTED,
        };
      }

      /**
       * todo: handle need clarification flow
       */
      return {
        ...statusReturnObj,
        status: MORE_PAYMENT_METHOD_STATUS.IN_PROGRESS,
      };
    }

    return {
      eddStatus: null,
      vKycStatus: null,
      kycDocumentStatus: null,
      kycRejectedReason: null,
      vKycRejectedReason: '',
      status: MORE_PAYMENT_METHOD_STATUS.NOT_ABLE_TO_FETCH,
    };
  },
);

export const createVCipLink = createAsyncThunk(
  'unlockIntlPaymentMethods/createVCipLink',
  async (name: string) => {
    try {
      const { success, data } = await merchantFetch({
        url: 'vkyc',
        method: 'POST',
        mode: 'live',
        data: {
          name,
        },
      });

      if (success && data) {
        return data;
      }

      return null;
    } catch {
      throw new Error('Failed to create VCIP link! Please try again in sometime.');
    }
  },
);

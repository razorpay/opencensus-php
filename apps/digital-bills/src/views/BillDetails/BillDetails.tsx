import React, { Fragment } from 'react';
import { Box, useToast, Spinner } from '@razorpay/blade/components';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useParams, useNavigate, useLocation } from 'react-router-dom';

import { graphqlRequest, graphqlRequestMutation } from '@apps/digital-bills/src/utils/graphql';
import { queryClient } from '@apps/digital-bills/src/bootstrap/Wrapper/Wrapper';
import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import makePhoneNumber from '@apps/digital-bills/src/utils/helpers/makePhone';
import BillDetailContainer from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/BillDetailContainer';
import DeleteModal from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/DeleteModal';
import ResendModal from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ResendModal';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import { useBillDetailsStore } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/stores/billDetailsStore';
import BillPreviewContainer from '@apps/digital-bills/src/views/BillDetails/containers/BillPreviewContainer/BillPreviewContainer';
import {
  BILL_DELETE_BY_ID,
  BILL_RESEND,
} from '@apps/digital-bills/src/views/BillDetails/mutations';
import { BILL_BY_ID_DATA_QUERY } from '@apps/digital-bills/src/views/BillDetails/queries';
import { BillByIdResponse } from '@apps/digital-bills/src/views/BillDetails/types';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import { ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';
import { verifyGqlErrorResponse } from '@apps/digital-bills/src/utils/helpers/verifyGqlErrorResponse';

const BillDetails = (): React.ReactElement => {
  const params = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const { show } = useToast();
  const { deleteModalIsOpen, onDeleteModalDismiss, resendModalIsOpen, onResendModalDismiss } =
    useBillDetailsStore();
  const { billsFilterPayload, filtersResetAt } = useBillsTablePayloadStore();
  const { id } = params;
  const legacyEntityId = new URLSearchParams(location.search).get('legacyEntityId');

  const {
    data: billResponse,
    isFetching: isBillInfoLoading,
    isError: isErrorInBillDetails,
    error: billError,
    refetch,
  } = useQuery<BillByIdResponse>({
    queryKey: ['bill_by_id', id],
    refetchOnWindowFocus: false,
    retry: false,
    queryFn: () =>
      graphqlRequest({
        document: BILL_BY_ID_DATA_QUERY,
        variables: { id },
      }),
  });

  const isBillViewAccessDenied = verifyGqlErrorResponse(billError);

  const { mutate: billDeleteMutate, isLoading: isBillDeleteLoading } = useMutation({
    mutationFn: () =>
      graphqlRequestMutation({
        document: BILL_DELETE_BY_ID,
        variables: { id },
      }),
    onSuccess: () => {
      show({
        type: 'informational',
        content: 'Bill deleted successfully!',
        onDismissButtonClick: () => {
          onDeleteModalDismiss();
        },
      });
      window.razorAnalytics?.trackStepEvent?.({ eventName: 'bill_deleted' });
      onDeleteModalDismiss();
      // Use relative routing to work in MFE
      navigate(new URL('..', window.origin + location.pathname));
      queryClient.invalidateQueries({
        queryKey: ['bills_table_data', billsFilterPayload.offset, filtersResetAt],
      });
    },
    onError: (error) => {
      const isBillDeleteAccessDenied = verifyGqlErrorResponse(error);
      show({
        type: 'informational',
        color: 'negative',
        content: isBillDeleteAccessDenied
          ? 'Access denied to delete bill'
          : 'Something went wrong while deleting the bill!',
      });
      window.razorAnalytics?.trackErrorResponse?.({ eventName: 'bill_deletion_failed' });
    },
  });

  const { mutate: billResendMutate, isLoading: isResendModalLoading } = useMutation({
    mutationFn: ({
      email,
      phone,
    }: {
      email?: string;
      phone?: { countryCode?: string; number: string };
    }) =>
      graphqlRequestMutation({
        document: BILL_RESEND,
        variables: { id, email, phone },
      }),
    onSuccess: () => {
      onResendModalDismiss();
      show({
        type: 'informational',
        content: 'Bill resent successfully!',
        onDismissButtonClick: () => {
          onResendModalDismiss();
        },
      });
      window.razorAnalytics?.trackStepEvent?.({ eventName: 'bill_resent' });
    },
    onError: (error) => {
      const isBillResendAccessDenied = verifyGqlErrorResponse(error);
      show({
        type: 'informational',
        color: 'negative',
        content: isBillResendAccessDenied
          ? 'Access denied to resend bill'
          : 'Something went wrong while resending the bill!',
      });
      window.razorAnalytics?.trackErrorResponse?.({ eventName: 'bill_resend_failed' });
    },
  });

  if (!id) return <ErrorPage description={ERROR_PAGE_DESCRIPTION} />;
  if (isBillInfoLoading) {
    return (
      <Box display="flex" justifyContent="center" height="100%">
        <Spinner accessibilityLabel="Bill info loading" />
      </Box>
    );
  }
  if (isErrorInBillDetails || !billResponse)
    return (
      <Box marginTop="spacing.9">
        <RetryOnError
          errorText="Error in fetching Bill info"
          retryFn={!isBillViewAccessDenied ? refetch : undefined}
        />
      </Box>
    );

  const {
    billById: {
      user,
      brand,
      visits,
      transactionType,
      dates,
      store,
      invoice,
      deliveryReport,
      signedToken,
    },
  } = billResponse;

  return (
    <Fragment>
      {/* Delete Modal*/}
      <DeleteModal
        modalProps={{
          isOpen: deleteModalIsOpen,
          onDismiss: onDeleteModalDismiss,
        }}
        onDelete={billDeleteMutate}
        isLoading={isBillDeleteLoading}
      />

      {/* Resend Modal */}
      <ResendModal
        modalProps={{
          isOpen: resendModalIsOpen,
          onDismiss: onResendModalDismiss,
        }}
        onResend={({ email, contactNo }): void => {
          billResendMutate({
            email,
            phone: {
              countryCode: '',
              number: contactNo,
            },
          });
        }}
        isLoading={isResendModalLoading}
        formValues={{
          email: user?.email,
          phoneNo: makePhoneNumber(user?.phone),
        }}
      />
      <Box display="flex" flexWrap="wrap" gap="spacing.6">
        <Box flex={1.5} minWidth={{ base: '300px', s: 'auto' }}>
          <BillDetailContainer
            amount={invoice?.amount?.value}
            timestamp={dates?.createdAt}
            transactionType={transactionType}
            invoiceNo={invoice?.number}
            billId={id}
            email={user?.email}
            contactNo={makePhoneNumber(user?.phone)}
            storeAddress={store?.address?.displayAddress}
            brandName={brand?.name}
            brandLogo={brand?.logo}
            visits={visits}
            deliveryReport={deliveryReport}
            legacyEntityId={legacyEntityId}
          />
        </Box>
        <Box flex={1} minWidth={{ base: '300px', s: '350px' }} minHeight="60vh">
          <BillPreviewContainer id={legacyEntityId} signedToken={signedToken} />
        </Box>
      </Box>
    </Fragment>
  );
};

export default BillDetails;

import { useState } from 'react';
import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useFormik, FormikValues } from 'formik';
import { useStore } from 'shell/commonStore';

// eslint-disable-next-line no-restricted-imports
import rolesList from 'merchant/helpers/permissions/roles-list';
import Invitation from 'merchant/models/Invitation';
import MerchantUser from 'merchant/models/Team';

import useConfirmModal from './ConfirmModal';
import {
  sendInviteApi,
  resendSmsApi,
  deleteInvitationApi,
  deleteMemberApi,
  updateMemberApi,
} from './api';
import { getInviteMemberValidation } from './constants';
import { InvitationT, InviteMemberDataT, TableItemT, UpdateMemberApiT } from './types';

interface DeleteMemberT {
  id: string;
  isConfirmed: boolean;
}

const INITIAL_FORM_VALUE: InviteMemberDataT = {
  role: 'partner_agent',
  name: '',
  contactMobile: '',
  id: '',
};

const INVITATIONS_QUERY_KEY = ['/api/live/invitations'];
const MERCHANTS_USERS_KEY = ['/merchants-users'];

const invitationModel = new Invitation();
const merchantUserModel = new MerchantUser();

const fetchInvitations = async (): Promise<InvitationT[]> => {
  const invitations = await invitationModel.fetchAll();
  return invitations.data.items;
};

const fetchMerchantUsers = async () => {
  const merchantUsers = await merchantUserModel.fetchAll();
  return merchantUsers.data.items.map((item) => ({ ...item, confirmed: true })); //confirmed field is only present for email invitation accepted users, not mobile OTP. Adding this manually
};

const useManageTeamPosEkyc = () => {
  const { userName, showNotification, countryCode } = useStore((state) => ({
    userName: state.session.user.name as string,
    showNotification: state.showNotification,
    countryCode: state.session.user.merchant!.country_code,
  }));

  const dialCode = getDialCodeByCountryCode(countryCode);

  const { confirm, renderConfirmModal } = useConfirmModal();

  const {
    error: invitationsError,
    data: invitationsData = [],
    isLoading: isInvitationsLoading,
  } = useQuery({
    queryKey: INVITATIONS_QUERY_KEY,
    queryFn: fetchInvitations,
    refetchOnWindowFocus: false,
  });

  const {
    error: merchantUsersError,
    data: merchantUsersData = [],
    isLoading: isMerchantUsersLoading,
  } = useQuery({
    queryKey: MERCHANTS_USERS_KEY,
    queryFn: fetchMerchantUsers,
    refetchOnWindowFocus: false,
  });

  const queryClient = useQueryClient();

  const isLoading = !!isInvitationsLoading || !!isMerchantUsersLoading;
  const isError = !!invitationsError || !!merchantUsersError;

  const posAgentUsers: TableItemT[] = [...invitationsData, ...merchantUsersData]
    .filter((userData) => userData.role === rolesList.PARTNER_AGENT)
    .map((userData) => ({
      name: userData.name || userData.metadata?.name || '', //for completed users, userData.name and for invitations metadata.name is used.
      contactMobile: userData?.contact_mobile || '',
      role: userData.role,
      isConfirmed: !!userData.confirmed,
      id: userData.id,
    }));

  const [isMemberModalOpen, setIsMemberModalOpen] = useState(false);

  const handleError = (e: any) => {
    // got error back in api response
    if (e.errors) {
      showNotification({ type: 'error', message: e.errors[0] || 'Something went wrong' });
      return;
    }

    // client side code error
    //TODO: send errors to sentry
    showNotification({
      type: 'error',
      message: e.message || 'Something went wrong',
    });
  };

  const resendSms = async ({ id, contactMobile }: { id: string; contactMobile: string }) => {
    try {
      await resendSmsApi({
        id,
        senderName: userName,
      });

      showNotification({
        type: 'success',
        message: `Invitation has been successfully sent to ${contactMobile}`,
      });
    } catch (e: any) {
      handleError(e);
    }
  };

  const deleteMemberCallback = async ({ id, isConfirmed }: DeleteMemberT) => {
    try {
      if (isConfirmed) {
        await deleteMemberApi({ id });
      } else {
        await deleteInvitationApi({ id });
      }

      const successMessage = isConfirmed
        ? 'Member removed successfully from the team'
        : 'Invitation successfully cancelled';

      showNotification({
        type: 'success',
        message: successMessage,
      });

      if (isConfirmed) {
        queryClient.setQueryData<TableItemT[]>(MERCHANTS_USERS_KEY, (prev) => {
          return prev!.filter((user) => user.id !== id);
        });
      } else {
        queryClient.setQueryData<TableItemT[]>(INVITATIONS_QUERY_KEY, (prev) => {
          return prev!.filter((user) => user.id !== id);
        });
      }

      // });
    } catch (e: any) {
      handleError(e);
    }
  };

  const deleteMember = ({ id, isConfirmed }: DeleteMemberT) => {
    const confirmMessage = isConfirmed
      ? 'Are you sure you want to remove this member from the team?'
      : 'Are you sure you want to cancel this invitation?';
    confirm({
      onConfirm: () => {
        return deleteMemberCallback({ id, isConfirmed });
      },
      message: confirmMessage,
    });
  };

  const updateMember = async ({ id, role, name }: UpdateMemberApiT) => {
    try {
      await updateMemberApi({
        name,
        role,
        id,
      });

      queryClient.setQueryData<TableItemT[]>(INVITATIONS_QUERY_KEY, (prev) => {
        return prev!.map((obj) =>
          obj.id === id
            ? {
                ...obj,
                name,
                role,
              }
            : obj,
        );
      });

      showNotification({
        type: 'success',
        message: 'Invitation has been updated successfully',
      });
      closeModal();
    } catch (e: any) {
      handleError(e);
    }
  };

  const onFormSubmit: FormikValues['handleSubmit'] = async (formData: InviteMemberDataT) => {
    try {
      // updating existing member details
      if (formData.id) {
        await updateMember({ id: formData.id, role: formData.role, name: formData.name });
        return;
      }

      // sending invite
      const response = await sendInviteApi({
        name: formData.name,
        contactMobile: formData.contactMobile,
        role: formData.role,
        senderName: userName,
        dialCode,
      });

      queryClient.setQueryData<TableItemT[]>(INVITATIONS_QUERY_KEY, (prev) => {
        return [...prev!, response.data];
      });

      showNotification({
        type: 'success',
        message: `Invitation has been successfully sent to ${formData.contactMobile}`,
      });
      closeModal();
    } catch (e) {
      handleError(e);
    }
  };

  const formik = useFormik<InviteMemberDataT>({
    initialValues: INITIAL_FORM_VALUE,
    onSubmit: onFormSubmit,
    validationSchema: getInviteMemberValidation({ countryCode }),
    validateOnBlur: true,
    validateOnMount: false,
    // teamLead: '' to be present for V2
  });

  const openInviteModal = (): void => {
    setIsMemberModalOpen(true);
    formik.resetForm();
    formik.setErrors({});
  };

  const openEditModal = (userData: InviteMemberDataT): void => {
    formik.setValues({
      id: userData.id,
      role: userData.role,
      name: userData.name,
      contactMobile: userData.contactMobile,
    });
    setIsMemberModalOpen(true);
  };

  function closeModal(): void {
    setIsMemberModalOpen(false);
    // modal close animation takes some time
    setTimeout(() => formik.setValues(INITIAL_FORM_VALUE), 300);
  }

  return {
    isMemberModalOpen,
    openInviteModal,
    openEditModal,
    closeModal,
    formik,
    posAgentUsers,
    resendSms,
    deleteMember,
    updateMember,
    isLoading,
    isError,
    renderConfirmModal,
    dialCode,
  };
};

export default useManageTeamPosEkyc;

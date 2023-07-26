import { merchantFetch } from 'merchant/utils/ajax';
import { downloadFile } from './utility';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getDeviceSource } from 'merchant/components/Support/getCommonSupportProperties';
import { TICKET_BASE_URL } from 'merchant/reducers/config';

export const fetchFircFiles = (month, year) => {
  return merchantFetch({
    url: 'merchant/firs',
    method: 'get',
    data: {
      month,
      year,
    },
  });
};

export const fetchFircFileUrl = (data) => {
  return merchantFetch({
    url: 'merchant/firs/content',
    method: 'get',
    data,
  });
};

export const downloadFiles = (obj) => {
  fetchFircFileUrl(obj)
    .then((response) => {
      downloadFile(response);
      return true;
    })
    .catch((err) => {
      throw new Error(err);
    })
    .finally(() => {
      analyticsTrack({
        objectName: 'FIRC File',
        actionName: 'download',
        screen: 'profile',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toLumberjack: true,
      });
    });
};

export const createSupportTicketForPurposeCode = async ({
  user,
  oldPurposeCode,
  newPurposeCode,
}) => {
  const purposeCodeTickets = await merchantFetch({
    url: TICKET_BASE_URL,
    mode: 'live',
  });

  // check if purpose code ticket exists
  if (Array.isArray(purposeCodeTickets?.data?.results)) {
    const existingTicket = purposeCodeTickets.data.results.find(
      (ticket) => ticket.description_text?.toLowerCase().indexOf('update my purpose code') > -1,
    );

    // if purpose code ticket exists then don't create a new one
    if (existingTicket) {
      return {
        duplicate: true,
      };
    }
  }

  const ticketData = new FormData();
  ticketData.set('email', user.email);
  ticketData.set('name', user.name);
  ticketData.set('phone', user.contact_mobile);
  ticketData.set(
    'description',
    `Please update my purpose code from ${oldPurposeCode} to ${newPurposeCode}`,
  );
  ticketData.set('subject', `[Merchant] Account related assistance`);
  ticketData.set('custom_fields[cf_merchant_id]', user.id);
  ticketData.set('tags[]', 'purpose_code_update');
  ticketData.set('custom_fields[cf_merchant_id_dashboard]', `merchant_dashboard_${user.id}`);
  ticketData.set('custom_fields[cf_new_requester_category]', 'Merchant');
  ticketData.set('custom_fields[cf_new_requester_sub_category]', 'Account related assistance');
  ticketData.set('custom_fields[cf_creation_source]', getDeviceSource());

  const response = await merchantFetch({
    url: TICKET_BASE_URL,
    mode: 'live',
    method: 'POST',
    data: ticketData,
  });

  if (response?.data?.ticket_id) {
    return { created: true };
  }

  return { created: false };
};

import React from 'react';
import { CHECKOUT_API_URL } from '@apps/shell/src/env';
import { PAYMENT_LINKS_CUSTOM_NOTES } from './constants/custom-notes';
import {
  PL_EXTRA_FORM_FIELDS,
  PL_DEFAULT_EXPIRY_IN_HOURS,
  PL_CUSTOMIZED_FIELDS,
  PL_DEFAULT_CUSTOMIZED_FIELDS,
  PL_ENABLE_CUSTOMER_NAME_FIELD,
} from './constants/payment-links';
import { isBankingOriginRequest } from '@apps/shell/src/server/utils';
import { Request } from 'express';

export const generateRzpBEControllerScripts = (
  req: Request,
  appLocals: { user: any; org: any; clientTemplate: string },
) => {
  // window
  const org = appLocals.org;
  const currentMerchantId = appLocals.user.current;

  // Note: Used earlier for legacy purpose. Not used now.
  // const { newNotifications, notifications, oldNotifications } = new NotificationService(
  //   appLocals,
  // ).getNotifications();

  // TODO: [IMP] Verify this flow, something related to checkout.
  const checkoutApiHost = CHECKOUT_API_URL;

  // Duplicated from BE, payment links
  const customNotes = PAYMENT_LINKS_CUSTOM_NOTES[currentMerchantId] || [];
  const plExpiryInHrs = PL_DEFAULT_EXPIRY_IN_HOURS[currentMerchantId] || null;
  const plExtraFields = PL_EXTRA_FORM_FIELDS[currentMerchantId] || [];
  const plCustomizedFormFields =
    PL_CUSTOMIZED_FIELDS[currentMerchantId] || PL_DEFAULT_CUSTOMIZED_FIELDS;
  const isPlCustomerNameFieldEnabled = PL_ENABLE_CUSTOMER_NAME_FIELD[currentMerchantId] || null;

  const sessionId = req?.cookies?.['rzp_usr_session'] || '';
  const isBankingRequest = isBankingOriginRequest(req);
  return (
    <script
      key="rzp-miscellaneous"
      dangerouslySetInnerHTML={{
        __html: `
        window.ONE_DASHBOARD = ${JSON.stringify(
          Boolean(appLocals.clientTemplate === 'one-dashboard'),
        )};
        window.rzp_org = ${JSON.stringify(org)};
        window.checkout_api_host = ${JSON.stringify(checkoutApiHost)};
        window.custom_notes = ${JSON.stringify(customNotes)};
        window.pl_expiry_in_hrs = ${JSON.stringify(plExpiryInHrs)};
        window.pl_extra_fields = ${JSON.stringify(plExtraFields)};
        window.pl_customized_form_fields = ${JSON.stringify(plCustomizedFormFields)};
        window.is_pl_customer_name_field_enabled = ${isPlCustomerNameFieldEnabled};
        window.session_id = ${JSON.stringify(sessionId)};
        window.is_banking_request = ${JSON.stringify(isBankingRequest)};`,
      }}
    />
  );
};

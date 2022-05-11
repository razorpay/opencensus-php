import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, humanize } from 'common/utils/rzp-utils';

const commonProp = {
  origin: 'dashboard',
};

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(eventName, data) {
    lumberjackTrack(
      window.rzpQ.paymentLinks().interaction(eventName, {
        ...commonProp,
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName: `${humanize(objectName)}`,
      actionName,
      screen: 'Create Payment Link',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...commonProp,
        ...properties,
      },
    });
  }

  return {
    resendStart: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToSegment('payment link resend start', 'click', prop);
      sendToLumberjack('pl.resend.start', prop);
    },
    resendClose: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToSegment('payment link resend close', 'clicked', prop);
      sendToLumberjack('pl.resend.close', prop);
    },
    resendIssue: () => {
      sendToSegment('merchant resend', 'click');
      sendToLumberjack('pl.resend.issue');
    },
    resendSuccess: (close) => {
      const property = {
        close,
      };
      sendToSegment('resend success toast', 'click', property);
      sendToLumberjack('pl.resend.issue.success', property);
    },

    onCopyClick: () => {
      sendToLumberjack(`pl.create.copy`);
      sendToSegment(`payment link create copy`, 'clicked', `payment link`);
    },
    onDetailsView: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.details_view`, prop);
      sendToSegment(`payment link update details view`, 'clicked', prop);
    },
    onDeactivate: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.deactivate`, prop);
      sendToSegment(`payment link update deactivate`, 'clicked', prop);
    },
    onDeactivateSuccess: (paymentLinkType, close) => {
      const prop = {
        type: paymentLinkType,
        close,
      };
      sendToLumberjack(`pl.deactivate.success`, prop);
      sendToSegment(`payment link deactivate success`, 'clicked', prop);
    },
    onDeactivateConfirm: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.deactivate_confirm`, prop);
      sendToSegment(`payment link update deactivate confirm`, 'clicked', prop);
    },
    onDeactivateAbort: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.deactivate_abort`, prop);
      sendToSegment(`payment link update deactivate abort`, 'clicked', prop);
    },
    onUpdatePartial: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.partial`, prop);
      sendToSegment(`payment link update partial`, 'clicked', prop);
    },
    onUpdateReciept: (eventName, paymentLinkType, modified) => {
      const prop = {
        type: paymentLinkType,
        modified,
      };
      sendToLumberjack(`pl.update.${eventName}`, prop);
      sendToSegment(`payment link update ${humanize(eventName)}`, 'clicked', prop);
    },
    updateReferenceId: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.referenceID`, prop);
      sendToSegment(`payment link update referenceId`, 'clicked', prop);
    },
    abortReferenceId: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.referenceID_cancel`, prop);
      sendToSegment(`payment link update referenceId cancel`, 'clicked', prop);
    },
    saveReferenceId: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.referenceID.success`, prop);
      sendToSegment(`payment link update referenceId success`, 'clicked', prop);
    },
    updateReminderTick: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.reminder_tick`, prop);
      sendToSegment(`payment link update reminder tick`, 'clicked', prop);
    },
    updateExpiry: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.expiry_tick`, prop);
      sendToSegment(`payment link update expiry tick`, 'clicked', prop);
    },
    saveUpdateExpiry: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.expiry.success`, prop);
      sendToSegment(`payment link update expiry success`, 'clicked', prop);
    },
    cancelUpdateExpiry: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.expiry_cancel`, prop);
      sendToSegment(`payment link update expiry cancel`, 'clicked', prop);
    },
    updateNotes: (paymentLinkType, modified) => {
      const prop = {
        type: paymentLinkType,
        modified,
      };
      sendToLumberjack(`pl.update.notes`, prop);
      sendToSegment(`payment link update notes`, 'clicked', prop);
    },
    updateNotesClosed: (paymentLinkType, modified) => {
      const prop = {
        type: paymentLinkType,
        modified,
      };
      sendToLumberjack(`pl.update.notes_closed`, prop);
      sendToSegment(`payment link update notes closed`, 'clicked', prop);
    },
    onClone: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.clone.start`, prop);
      sendToSegment(`payment link update clone start`, 'clicked', prop);
    },
    onResend: (paymentLinkType) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.resend.start`, prop);
      sendToSegment(`payment link update resend start`, 'clicked', prop);
    },
    notifyLink: (paymentLinkType, eventName) => {
      const prop = {
        type: paymentLinkType,
      };
      sendToLumberjack(`pl.update.resend.${eventName}`, prop);
      sendToSegment(`payment link update resend ${humanize(eventName)}`, 'clicked', prop);
    },
    paymentUpdateDetail: (eventName, modified) => {
      sendToLumberjack(eventName, modified);
      sendToSegment(`${humanize(eventName)}`, 'clicked', modified);
    },
    updateNotesClose: (eventName) => {
      sendToLumberjack(eventName);
      sendToSegment(eventName, 'clicked');
    },
    updateReminderEnable: (eventName, properties) => {
      sendToLumberjack(eventName, properties);
      sendToSegment(eventName, 'clicked', properties);
    },
    paymentLinkDetailsUpdateView: (eventName, properties) => {
      sendToLumberjack(eventName, properties);
      sendToSegment(eventName, 'clicked', properties);
    },
    init: (_lumberjackTrack) => {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();

import { useState, useEffect, useRef, useCallback } from 'react';
import { createSidetab } from '@typeform/embed';
import { getItem, setItem } from 'common/utils/localStorage';
import { isMobileDevice } from 'merchant/components/Home/data';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import store from 'merchant/store';
import moment from 'moment';

export default function usePartnerPageNPS(surveyID, isUserPartner = false, isPartnerMTU = false) {
  const user = store.getState().session.user;

  const [partnerNPSSurveyPopup, setPartnerNPSSurveyPopup] = useState();
  const refPartnerNPSEnableTypeForm = useRef(null);
  const npsSurveyStatusFlag = `razorpay_partner_nps_survey_showed_${surveyID}`;

  const closePartnerSurvey = () => {
    setPartnerNPSSurveyPopup(false);
    refPartnerNPSEnableTypeForm.current?.unmount();
  };

  const handleSubmit = useCallback(() => {
    closePartnerSurvey();
    setItem(npsSurveyStatusFlag, 'submit');
  }, []);

  const loadFuxData = async () => {
    try {
      const { isOwner } = user;
      if (!isOwner) return false;
      const { data } = await merchantFetch({
        url: 'partner/first_user_experience',
        method: 'get',
      });
      const { first_earning_generated, first_submerchant_added } = data;
      const partnerMTUCheck = isPartnerMTU ? first_earning_generated : true;
      return partnerMTUCheck && first_submerchant_added;
    } catch (_) {
      return false;
    }
  };

  const checkUserDetails = (created_at, activation_status) => {
    const noOfDays = moment(moment(new Date()).format('MM-DD-YYYY')).diff(
      moment.unix(created_at).format('MM-DD-YYYY'),
      'days',
    ); //calculating created and present days difference
    return (
      noOfDays >= 30 &&
      (activation_status === 'activated' || activation_status === 'activated_mcc_pending')
    );
  };

  // conditions to show survey popup, required checks can be added or removed from here
  const isShowPopup = useCallback(
    async (created_at, activation_status) => {
      const fuxData = await loadFuxData();
      const userCheck = isUserPartner ? checkUserDetails(created_at, activation_status) : true;
      const isShowNPS = fuxData && userCheck;
      analyticsTrack({
        objectName: 'partner nps survey check',
        actionName: 'result',
        screen: 'partnership',
        properties: {
          isShowNPS,
          ...getCommonAnalyticsProperties(user),
        },
      });
      return isShowNPS;
    },
    [user],
  );

  const createSurveyForm = useCallback(async () => {
    const { created_at, activation_status, isPartnershipNPS, email, id } = user;
    if (
      isPartnershipNPS &&
      (getItem(npsSurveyStatusFlag) === null || getItem(npsSurveyStatusFlag) === 'show')
    ) {
      const show = await isShowPopup(created_at, activation_status);
      if (show) {
        const PartnerNPSEnableTypeForm = createSidetab(
          surveyID, // partner survey
          {
            width: isMobileDevice() ? 340 : 500,
            buttonText: 'Feedback',
            hideHeaders: true,
            hideFooters: true,
            hidden: {
              mid: `${id}`,
              source: 'partner_page',
              email: `${email}`,
            },
            onClose: closePartnerSurvey,
            onSubmit: handleSubmit,
          },
        );
        refPartnerNPSEnableTypeForm.current = PartnerNPSEnableTypeForm;
      }
      setPartnerNPSSurveyPopup(show);
    }
  }, [user, surveyID, handleSubmit, isShowPopup]);

  // Show or Hide survey form
  const getSurveyForm = useCallback(() => {
    if (partnerNPSSurveyPopup && getItem(npsSurveyStatusFlag) === null) {
      refPartnerNPSEnableTypeForm.current?.open();
      setItem(npsSurveyStatusFlag, 'show');
    }
  }, [partnerNPSSurveyPopup]);

  useEffect(() => {
    createSurveyForm();
    return () => {
      closePartnerSurvey();
    };
  }, [createSurveyForm]);

  useEffect(() => {
    getSurveyForm();
  }, [getSurveyForm]);
}

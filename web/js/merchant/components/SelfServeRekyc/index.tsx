import React, { useEffect, useState } from 'react'
import { connect } from 'react-redux';

import { useGetRekycDetails } from 'merchant/components/SelfServeRekyc/hooks/useGetRekycDetails';

import { RekycBannerInfo, RekycStepInfo, SelfServeRekycNotificationProps, RekycModalInfo, RekycDetailsApiData } from 'merchant/components/SelfServeRekyc/types';

import {
  DYNAMIC_REKYC_STATUS,
  FINAL_STEPS_MAP,
  MERCHANT_ACTION,
  NON_OWNER_MODAL_CONTENT,
  NON_OWNER_REKYC_BANNER_INFO,
  OWNER_MODAL_CONTENT,
  OWNER_REKYC_BANNER_INFO,
  SELF_SERVE_REKYC_HIDE_MODAL,
  STATUSES_TO_SHOW_TIMELINE,
} from 'merchant/components/SelfServeRekyc/constants';

import {
  getDaysFromDeadline,
  getDeadlineDate,
  getRekycModalIsOpen,
  getRekycStatus,
  getTimelineString,
  shouldShowModal as shouldShowModalUtil
} from 'merchant/components/SelfServeRekyc/utils';

import RekycBanner from 'merchant/components/SelfServeRekyc/components/RekycBanner';
import RekycModalWrapper from 'merchant/components/SelfServeRekyc/containers/RekycModalWrapper';

const SelfServeRekycNotification: React.FC<SelfServeRekycNotificationProps> = (props) => {

  const {
    merchantId,
    role,
  } = props;

  const isOwnerOrAdmin = role === 'owner' || role === 'admin';

  const BANNER_INFO_MAP = isOwnerOrAdmin ? OWNER_REKYC_BANNER_INFO : NON_OWNER_REKYC_BANNER_INFO

  const MODAL_INFO_MAP = isOwnerOrAdmin ? OWNER_MODAL_CONTENT : NON_OWNER_MODAL_CONTENT;

  const [bannerDetails, setBannerDetails] = useState<RekycBannerInfo | null>(null);
  const [stepsInfo, setStepsInfo] = useState<RekycStepInfo | null>(null);
  const [modalInfo, setModalInfo] = useState<RekycModalInfo | null>(null);
  const [shouldShowModal, setShouldShowModal] = useState(false);
  const [rekycUrl, setRekycUrl] = useState('');

  const {
    data: rekycDetails,
  } = useGetRekycDetails({merchantId, showCurrentData: true});

  useEffect(() => {
    if(rekycDetails && Object.keys(rekycDetails).length){
      const rekycStatus = getRekycStatus(rekycDetails?.status);

      const ACTIONABLE_STATUSES = DYNAMIC_REKYC_STATUS;

      const isMerchantActionable = ACTIONABLE_STATUSES.includes(rekycStatus);

      let timelineKey = ''
      if(isMerchantActionable){
        timelineKey = getTimelineString(rekycDetails?.deadline);
        setRekycUrl(MERCHANT_ACTION[rekycStatus]);
      }

      if(timelineKey){
        setBannerDetails(BANNER_INFO_MAP[rekycStatus][timelineKey])
      }else{
        setBannerDetails(BANNER_INFO_MAP[rekycStatus])
      }

      if(STATUSES_TO_SHOW_TIMELINE.includes(rekycStatus)){
        if(timelineKey){
          setStepsInfo(FINAL_STEPS_MAP[rekycStatus][timelineKey])
        }else{
          setStepsInfo(FINAL_STEPS_MAP[rekycStatus])
        }
      }

      const isOpen = getRekycModalIsOpen(rekycStatus, rekycDetails?.deadline);
      setShouldShowModal(isOpen);
      if (isOpen) {
        if (timelineKey) {
          setModalInfo(MODAL_INFO_MAP[rekycStatus][timelineKey])
        } else {
          setModalInfo(MODAL_INFO_MAP[rekycStatus])
        }
        // run sideeffects
        const isModalConfigAvailable = localStorage.getItem(SELF_SERVE_REKYC_HIDE_MODAL);
        if (isModalConfigAvailable) {
          localStorage.removeItem(SELF_SERVE_REKYC_HIDE_MODAL);
        }
      }
    }
  }, [rekycDetails]);

  return (
    <>
      {
        bannerDetails ?
        <RekycBanner
          bannerDetails={bannerDetails}
          deadlineDate={getDeadlineDate(rekycDetails?.deadline)}
          daysFromDeadline={getDaysFromDeadline(rekycDetails?.deadline)}
          stepsInfo={stepsInfo}
          rekycUrl={rekycUrl}
          rekycStatus={rekycDetails?.status}
        /> : null
      }
      {
        shouldShowModal && modalInfo ?
        <RekycModalWrapper
          modalInfo={modalInfo}
          deadlineDate={getDeadlineDate(rekycDetails?.deadline)}
          daysFromDeadline={getDaysFromDeadline(rekycDetails?.deadline)}
          rekycUrl={rekycUrl}
          rekycStatus={rekycDetails?.status}
        /> : null
      }
    </>
  )
}

const mapStateToProps = (state: any) => ({
  merchantId: state.session.user.current,
  role: state.session.user.role,
})

export default connect(mapStateToProps, null)(SelfServeRekycNotification);
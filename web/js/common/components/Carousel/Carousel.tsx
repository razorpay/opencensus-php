import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { withRouter } from 'react-router';
import isEmpty from 'lodash/isEmpty';
import rTracking from 'react-tracking';
import Button from 'common/new-ui/Button';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import CarouselModal from 'common/components/Carousel/CarouselModal';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getAssetTrackingProperties } from '../../../merchant/models/GrowthService/commonUtils';
import sanitizer from 'common/utils/xss-sanitizer';

let slideIndex = 0;
const trackerBannerFirstImpression = {};
const Carousel = ({ carouselItem, openModal, tracking, history }): React.ReactElement => {
  let timer = 0;

  const bannerCardImp = (eventName, banner_id, banner_order) => {
    const trackData = carouselItem.filter(({ id }) => id === banner_id)?.[0]?.tracking_data;
    tracking?.trackEvent?.(
      window.rzpQ?.merchantActions().success(eventName, {
        banner_id,
        ...(banner_order ? { banner_order } : null),
        ...getAssetTrackingProperties(banner_id, { ...trackData, ...trackData?.tags, eventName }),
      }),
    );
  };

  const bannerInitiatedEvent = (eventName, banner_id, banner_order, tracking_data) => {
    tracking?.trackEvent?.(
      window.rzpQ?.merchantActions().initiated(eventName, {
        banner_id,
        ...(banner_order ? { banner_order } : null),
        ...getAssetTrackingProperties(banner_id, {
          ...tracking_data,
          ...tracking_data?.tags,
        }),
      }),
    );
  };

  const automaticSlide = () => {
    let i;
    const slides = document.getElementsByClassName('carouselCard');
    if (!slides) return;
    for (i = 0; i < slides.length; i++) {
      (slides[i] as any).style.display = 'none';
    }
    slideIndex++;
    if (slideIndex > slides.length) {
      slideIndex = 1;
    }
    (slides[slideIndex - 1] as any).style.display = 'flex';
    const bannerOrder = slides[slideIndex - 1]?.getAttribute('data-bannerOrder') || '';
    if (!trackerBannerFirstImpression[bannerOrder]) {
      bannerCardImp(
        'carousel_banner_notification1',
        slides[slideIndex - 1].getAttribute('id'),
        bannerOrder,
      );
      trackerBannerFirstImpression[bannerOrder] = true;
    }

    timer = setTimeout(automaticSlide, 5000); // Change image every 5 seconds
  };
  const manualSlide = (n) => {
    let i;
    const slides = document.getElementsByClassName('carouselCard');
    if (!slides) return;
    if (n > slides.length) {
      slideIndex = 1;
    }
    if (n < 1) {
      slideIndex = slides.length;
    }
    for (i = 0; i < slides.length; i++) {
      (slides[i] as any).style.display = 'none';
    }
    (slides[slideIndex - 1] as any).style.display = 'flex';

    const bannerOrder = slides[slideIndex - 1]?.getAttribute('data-bannerOrder') || '';

    if (!trackerBannerFirstImpression[bannerOrder]) {
      bannerCardImp(
        'carousel_banner_notification1',
        slides[slideIndex - 1].getAttribute('id'),
        bannerOrder,
      );
      trackerBannerFirstImpression[bannerOrder] = true;
    }
  };
  const plusSlides = (n) => {
    manualSlide((slideIndex += n));
  };
  const handleLeftNav = () => {
    plusSlides(-1);
    const slides = document.getElementsByClassName('carouselCard');

    bannerCardImp('carousel_banner_prev_button', slides[slideIndex - 1].getAttribute('id'), '');
  };
  const handleRightNav = () => {
    plusSlides(1);
    const slides = document.getElementsByClassName('carouselCard');
    bannerCardImp('carousel_banner_next_button', slides[slideIndex - 1].getAttribute('id'), '');
  };

  const handleCtaClick = ({
    l2_content = {},
    url,
    target,
    reload,
    tracking_data,
    banner_id,
    bannerOrder,
  }) => {
    if (isEmpty(l2_content)) {
      if (target) {
        window.open(url, '_blank');
      } else if (reload) {
        window.open(url);
      } else {
        history.push(url);
      }
    } else {
      openModal({
        size: 'medium',
        component: (
          <CarouselModal
            l2_content={l2_content}
            tracking_data={tracking_data}
            banner_id={banner_id}
            bannerOrder={bannerOrder}
            bannerInitiatedEvent={bannerInitiatedEvent}
            bannerCardImp={bannerCardImp}
          />
        ),
        className: 'bannerCarouselModal',
      });
    }
    bannerInitiatedEvent(
      'carousel_banner_notification1_cta1',
      banner_id,
      bannerOrder,
      tracking_data,
    );
  };

  useEffect(() => {
    automaticSlide();
    return () => {
      clearTimeout(timer);
    };
  }, []);

  const carouselList = carouselItem.map(
    ({ id, title, description, bg_image, m_image, buttons, l2_content, tracking_data }, index) => (
      <div className="carouselCard" id={id} data-bannerOrder={index + 1} key={`${id}_${index}`}>
        <div className="carouselCard__content">
          <div>{title}</div>
          <div dangerouslySetInnerHTML={{ __html: sanitizer(description) }} />
          <div>
            {buttons.map(({ label, id: ctaId, url, target, reload, style }) => (
              <Button
                key={ctaId}
                className={`btn ${style ? style : 'primaryCta'}`}
                onClick={() =>
                  handleCtaClick({
                    l2_content,
                    url,
                    target,
                    reload,
                    tracking_data,
                    banner_id: id,
                    bannerOrder: index + 1,
                  })
                }
              >
                {label}
              </Button>
            ))}
          </div>
        </div>
        <div className="carouselCard__image">
          <img src={isMobileDevice() ? m_image : bg_image} alt={title} />
        </div>
      </div>
    ),
  );
  return (
    <div className="carousel" id="carousel">
      <div className="carousel__left carouselNav" onClick={handleLeftNav}>
        <i className="i i-arrow-back arrowHover" />
      </div>
      <div className="carousel__list">{carouselList}</div>
      <div className="carousel__right carouselNav" onClick={handleRightNav}>
        <i className="i i-arrow-forward arrowHover" />
      </div>
    </div>
  );
};

export default compose<any>(
  rTracking({ page: 'CarouselBanner' }),
  withRouter,
  connect(null, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
  }),
)(Carousel);

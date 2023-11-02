import React, { useEffect } from 'react';
import { Carousel } from 'react-responsive-carousel';
import PPImage1 from 'assets/payment_pages/sell-products.jpg';
import PPImage2 from 'assets/payment_pages/fee.jpg';
import PPImage3 from 'assets/payment_pages/donations.jpg';
import PPImage4 from 'assets/payment_pages/events.jpg';
import StorefrontImage from 'assets/payment_pages/storefront-demo.jpg';
import Image from 'common/ui/Image';
import { PAYMENT_PAGES_TYPES } from '.';
import {
  TemplateSelectionModalContent,
  PurpleBackground,
  TemplateSelectionWrapper,
  IconWrapper,
  TemplateSelectionModal,
  InlineWrapper,
  FeatureWrapper,
  Tag,
  NewLabel,
  TemplateSelectionHeading,
} from './styled';
import {
  Title,
  Heading,
  Button,
  CheckIcon,
  ArrowRightIcon,
  CloseIcon,
} from '@razorpay/blade/components';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
// import 'react-responsive-carousel/lib/styles/carousel.min.css'; // requires a loader
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

interface IProps extends RouteComponentProps {
  handlePageType: (val: string) => void;
  isMobile: boolean;
}

const appendStylesheet = (src: string, id?: string): void => {
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = src;
  if (id) {
    // do nothing if id is already existing
    const element = document.getElementById(id);
    if (element) {
      console.log('Did not append stylesheet. Id is already in use');
      return;
    }
    link.id = id;
  }
  document.documentElement.appendChild(link);
};

const TemplateSelection = ({ handlePageType, isMobile, history }: IProps): React.ReactElement => {
  useEffect(() => {
    // TODO: Create a common component (to prevent the need to appendCSS)
    appendStylesheet(
      'https://cdn.jsdelivr.net/npm/react-responsive-carousel@3.2.23/lib/styles/carousel.min.css',
      'react-responsive-carousel',
    );
  }, []);

  const handleClose = () => {
    history.push('/paymentpages');
  };

  const buttonSize = isMobile ? 'medium' : 'large';

  const onCreatePaymentPageClick = () => {
    handlePageType(PAYMENT_PAGES_TYPES.payment_page);
    analyticsTrack({
      objectName: 'Payment page',
      actionName: 'clicked',
      screen: 'Select page of your choice',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        product_template: 'page',
      },
    });
  };

  const onCreateStorefrontPageClick = () => {
    handlePageType(PAYMENT_PAGES_TYPES.storefront);
    analyticsTrack({
      objectName: 'Storefront page',
      actionName: 'clicked',
      screen: 'Select page of your choice',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        product_template: 'storefront',
      },
    });
  };

  return (
    <TemplateSelectionModal showCloseBtn={false}>
      <TemplateSelectionModalContent>
        <PurpleBackground>
          <div className="pp-title-wrapper">
            <IconWrapper className="template">
              <i className="i i-template" />
            </IconWrapper>
            <Title size="small" type="normal" contrast="high">
              Select page of your choice
            </Title>
          </div>
          <IconWrapper className="close-icon" onClick={handleClose}>
            <CloseIcon color="feedback.icon.neutral.highContrast" size="large" />
          </IconWrapper>
        </PurpleBackground>
        <TemplateSelectionWrapper>
          <div className="left-container">
            <div className="image-wrapper">
              <Carousel
                // width={500}
                showThumbs={false}
                showStatus={false}
                showArrows={false}
                interval={5000}
                infiniteLoop
                autoPlay
                className="pp-template-section-carousel"
              >
                <Image src={PPImage1} alt="payment pages template 1" isWebp />
                <Image src={PPImage2} alt="payment pages template 2" isWebp />
                <Image src={PPImage3} alt="payment pages template 3" isWebp />
                <Image src={PPImage4} alt="payment pages template 4" isWebp />
              </Carousel>
              <Tag>
                <i className="i i-tag-outline mt-2 mr-2" />
                Sample Pages
              </Tag>
            </div>
            <Heading type="normal" size="large" weight="bold" contrast="low">
              Payment page
            </Heading>
            <div>Setup your own custom branded page. Collect payments for:</div>
            <FeatureWrapper>
              <InlineWrapper>
                <CheckIcon color="feedback.icon.positive.lowContrast" size="medium" />
                <span>Events & tickets</span>
              </InlineWrapper>
              <InlineWrapper>
                <CheckIcon color="feedback.icon.positive.lowContrast" size="medium" />
                <span>Donations</span>
              </InlineWrapper>
              <InlineWrapper>
                <CheckIcon color="feedback.icon.positive.lowContrast" size="medium" />
                <span>Fees</span>
              </InlineWrapper>
              <InlineWrapper>
                <CheckIcon color="feedback.icon.positive.lowContrast" size="medium" />
                <span>Courses</span>
              </InlineWrapper>
              <InlineWrapper>
                <span>& more</span>
              </InlineWrapper>
            </FeatureWrapper>
            <Button
              variant="primary"
              size={buttonSize}
              onClick={onCreatePaymentPageClick}
              icon={ArrowRightIcon}
              iconPosition="right"
              isFullWidth={isMobile}
            >
              Select Payment page
            </Button>
          </div>

          <div className="right-container">
            <div className="image-wrapper">
              <Image src={StorefrontImage} alt="payment pages stores template 1" isWebp />
              <Tag>
                <i className="i i-tag-outline mt-2 mr-2" />
                Sample Page
              </Tag>
            </div>
            {/* TODO: Refactor after blade's heading component allows multiple children */}
            <TemplateSelectionHeading>
              Storefront page
              <NewLabel marginLeft="8px">
                <i className="i i-star-outline mt-2" />
                <span>New</span>
              </NewLabel>
            </TemplateSelectionHeading>
            <p>
              Showcase products on your online storefront and start accepting orders. Add multiple
              images and detailed descriptions for your products & do lots more
            </p>
            <Button
              variant="primary"
              size={buttonSize}
              onClick={onCreateStorefrontPageClick}
              icon={ArrowRightIcon}
              iconPosition="right"
              isFullWidth={isMobile}
            >
              Select Storefront page
            </Button>
          </div>
        </TemplateSelectionWrapper>
      </TemplateSelectionModalContent>
    </TemplateSelectionModal>
  );
};

export default withRouter(TemplateSelection);

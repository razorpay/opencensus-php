// import { Link } from 'react-router-dom';
import React from 'react';
import { Link, ChevronLeftIcon } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import { withRouter } from 'shell/deprecated/withRouter';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
const OfferTypeSelectorWrapper = (props) => {
  const navigate = useNavigate();
  return <OfferTypeSelector navigate={navigate} {...props} />;
};
const OFFER_TYPES = [
  {
    key: 'basic',
    title: 'Discounts & Cash Backs',
    description:
      'Create offers for discounts or cash-backs to your customers when they make a payment.',
    img: '/img/offers/offer.png',
  },
  {
    key: 'subscription',
    title: 'Offers on Subscriptions',
    description: `Provide discounts on one or more auto-debit payments during a subscription cycle.`,
    img: '/img/offers/subscription.svg',
  },
];

class OfferTypeSelector extends React.PureComponent {
  handleTemplateSelection = (linkType) => () => {
    this.props.selectTemplate(linkType);
  };

  handleOnClick = (path) => {
    return this.props.navigate(path);
  };

  render() {
    const { props } = this;

    const content = (
      <div class="Offers--TypeSelection">
        <div class="slide-in">
          <div class="heading">Pick a promotion type</div>
        </div>
        <div class="TemplateCard-list">
          {OFFER_TYPES.map((templateData) => {
            return (
              <TemplateCard
                key={templateData.key}
                {...templateData}
                // eslint-disable-next-line no-undef
                {...(props.isTestMode && TEST_MODE_TYPES[templateData.key])}
                onClick={this.handleTemplateSelection(templateData.key)}
              />
            );
          })}
        </div>
      </div>
    );

    if (props.isModalView) {
      return (
        <ModalMask
          class="PaymentLinks--CreateV2--LinkTypeSelection Offers--TypeSelection"
          maskClosable={false}
        >
          <Modal class={content && 'animate-down'} showCloseBtn={false}>
            <Link
              icon={ChevronLeftIcon}
              color="white"
              iconPosition="left"
              size="large"
              marginLeft="spacing.5"
              marginTop="spacing.5"
              onClick={() => this.handleOnClick('/offers/')}
              variant="button"
            >
              Back to Dashboard
            </Link>
            <ModalContent>{content}</ModalContent>
          </Modal>
        </ModalMask>
      );
    }

    return <div class="StandAloneContainer">{content}</div>;
  }
}

class TemplateCard extends React.PureComponent {
  state = {};

  componentDidMount() {
    this.setState({
      isLoaded: true,
    });
  }

  render() {
    const { title, description, img, onClick, hoverText } = this.props;

    return (
      <div class="TemplateCard" onClick={onClick}>
        <img src={this.state.isLoaded ? img : null} />
        <div class="TemplateCard-details">
          {title}
          <div class="TemplateCard-desc">
            {description}
            {hoverText && <div class="hover-text">{hoverText}</div>}
          </div>

          <div class="link">
            <span>Create Now</span>
            <i class="i i-arrow-forward" />
          </div>
        </div>
      </div>
    );
  }
}
export default withRouter(OfferTypeSelectorWrapper);

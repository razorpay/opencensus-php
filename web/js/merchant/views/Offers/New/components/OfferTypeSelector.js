import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

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

export default class OfferTypeSelector extends React.PureComponent {
  handleTemplateSelection = (linkType) => () => {
    this.props.selectTemplate(linkType);
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
                {...templateData}
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
          <Link class="back-btn" to="/offers/">
            <i class="i i-chevron-left" />
            Back to Dashboard
          </Link>
          <Modal class={content && 'animate-down'} showCloseBtn={false}>
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

import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

const PAYMENT_LINK_TYPES = [
  {
    key: 'standard',
    title: 'Standard Payment Link',
    description:
      'Create a payment link with the payment modes enabled - credit card, debit card, UPI, netbanking, wallet',
    img: '/img/payment_links/standard_link.svg',
  },
  {
    key: 'upi',
    title: 'UPI Payment Link',
    description: 'Create a deep UPI intent link to collect UPI payments from the end consumer',
    img: '/img/payment_links/upi.png',
  },
];

export default function PaymentLinkSelector(props) {
  const content = (
    <div class="PaymentLinks--CreateV2--LinkTypeSelection">
      <div class="slide-in">
        <div class="heading">Pick a Payment Link Type</div>
      </div>
      <div class="TemplateCard-list">
        {PAYMENT_LINK_TYPES.map((templateData) => (
          <TemplateCard {...templateData} onClick={() => props.selectTemplate(templateData.key)} />
        ))}
      </div>
    </div>
  );

  if (props.isModalView) {
    return (
      <ModalMask class="PaymentLinks--CreateV2--LinkTypeSelection" maskClosable={false}>
        <Link class="back-btn" to="/paymentlinks/">
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

class TemplateCard extends React.PureComponent {
  state = {};

  componentDidMount() {
    this.setState({
      isLoaded: true,
    });
  }

  render() {
    const { title, description, img, onClick } = this.props;

    return (
      <div class="TemplateCard" onClick={onClick}>
        <img src={this.state.isLoaded ? img : null} />
        <div class="TemplateCard-details">
          {title}
          <div class="TemplateCard-desc">{description}</div>

          <div class="link">
            <span>Use this Payment Link</span>
            <i class="i i-arrow-forward" />
          </div>
        </div>
      </div>
    );
  }
}

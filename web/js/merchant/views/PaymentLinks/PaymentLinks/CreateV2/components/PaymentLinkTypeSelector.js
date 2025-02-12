import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { classList, getURLQueryParams } from 'common/utils/rzp-utils';

import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

import StandardLinkImage from 'assets/payment_links/standard_link.svg';
import UpiLinkImage from 'assets/payment_links/upi.png';

const PAYMENT_LINK_TYPES = [
  {
    key: 'standard',
    title: 'Standard Payment Link',
    description:
      'Create a classic payment link to collect payment from your customers in all payment methods.',
    img: StandardLinkImage,
  },
  {
    key: 'upi',
    title: 'UPI Payment Link',
    description: `Collect UPI payments from your customers, using UPI payment links, without knowing their UPI/VPA addresses.`,
    img: UpiLinkImage,
    className: 'upi-template',
  },
];

const TEST_MODE_TYPES = {
  upi: {
    key: 'upi',
    title: 'UPI Payment Link',
    description: `Collect UPI payments from your customers, using UPI payment links, without knowing their UPI/VPA addresses.`,
    hoverText:
      'UPI Payment Links is not supported in Test Mode. Please experience the product in Live Mode.',
    img: UpiLinkImage,
  },
};

export default class PaymentLinkSelector extends React.PureComponent {
  componentDidMount() {
    track.lj.linkTypeSelection.open();
  }

  handleTemplateSelection = (linkType) => () => {
    this.props.selectTemplate(linkType);

    track.lj.linkTypeSelection.select(linkType);
    track.segment.linkTypeSelection.select(linkType);
  };

  render() {
    const { props } = this;

    const content = (
      <div className="PaymentLinks--CreateV2--LinkTypeSelection">
        <div className="slide-in">
          <div className="heading">Pick a Payment Link Type</div>
        </div>
        <div className="TemplateCard-list">
          {PAYMENT_LINK_TYPES.map((templateData) => {
            const actionsDisabled = props.isTestMode && TEST_MODE_TYPES[templateData.key];
            const extraProps = !actionsDisabled && {
              onClick: this.handleTemplateSelection(templateData.key),
            };

            return (
              <TemplateCard
                key={templateData.key}
                {...templateData}
                {...(props.isTestMode && TEST_MODE_TYPES[templateData.key])}
                {...extraProps}
              />
            );
          })}
        </div>
      </div>
    );

    if (props.isModalView) {
      const { redirect } = getURLQueryParams(props?.history?.location?.search);
      return (
        <ModalMask className="PaymentLinks--CreateV2--LinkTypeSelection" maskClosable={false}>
          <Link className="back-btn" to={redirect ?? '/paymentlinks'}>
            <i className="i i-chevron-left" />
            Back to Dashboard
          </Link>
          <Modal className={content && 'animate-down'} showCloseBtn={false}>
            <ModalContent>{content}</ModalContent>
          </Modal>
        </ModalMask>
      );
    }

    return <div className="StandAloneContainer">{content}</div>;
  }
}

class TemplateCard extends React.PureComponent {
  render() {
    const { title, description, img, onClick, hoverText, className } = this.props;

    return (
      <div className={classList('TemplateCard', !onClick && 'disabled', className)} onClick={onClick}>
        <img src={img} />
        <div className="TemplateCard-details">
          {title}
          <div className="TemplateCard-desc">
            {description}
            {hoverText && <div className="hover-text">{hoverText}</div>}
          </div>

          {onClick && (
            <div className="link">
              <span>Create Now</span>
              <i className="i i-arrow-forward" />
            </div>
          )}
        </div>
      </div>
    );
  }
}

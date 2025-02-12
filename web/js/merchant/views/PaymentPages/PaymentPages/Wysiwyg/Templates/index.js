import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import ShowWhen from 'merchant/components/ShowWhen';
import META from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/Templates/meta';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import {
  trackGoBackDashboard,
  trackTemplateSelection,
  trackStartCreation,
} from 'merchant/views/PaymentPages/PaymentPages/ga';

const createYourOwn = {
  card: {
    title: 'Create your Own',
    description: 'Got your own idea? Start with a clean slate.',
    img: '/img/payment_pages/start_from_scratch.jpg',
  },
};

class WysiwygTemplate extends React.PureComponent {
  selectTemplate = (templateKey, label, quillPrefill, title) => {
    return () => {
      this.props.selectTemplate(quillPrefill, templateKey);
      this.props.onClose();

      track.wysiwyg.selectTemplate(title);
      trackTemplateSelection(title || createYourOwn.card.title);
      trackStartCreation(title || createYourOwn.card.title);
    };
  };

  render() {
    const { showCustomTemplate, isBatchPaymentPages, user } = this.props;
    if (isBatchPaymentPages) {
      this.selectTemplate('custom', null);
      return '';
    }

    const countryCode = user.merchant.country_code;
    return (
      <ModalMask maskClosable={false} className="payment-pages-v2-templates view-1" isBlur={true}>
        <Link className="back-btn" to="/paymentpages/" onClick={trackGoBackDashboard}>
          <i className="i i-chevron-left" />
          Back to Dashboard
        </Link>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div className="slide-in">
              <div className="heading">Choose from the templates</div>
              <p>You can choose one of the templates from below</p>
            </div>

            <div className="TemplateCard-list">
              <ShowWhen additionalCondition={() => showCustomTemplate}>
                <TemplateCard
                  title={createYourOwn.card.title}
                  description={createYourOwn.card.description}
                  img={createYourOwn.card.img}
                  selectTemplate={this.selectTemplate('custom', null)}
                />
              </ShowWhen>
              {Object.keys(META).map((m, k) => {
                if (META.hasOwnProperty(m)) {
                  const item = META[m][countryCode];
                  return (
                    <TemplateCard
                      key={k}
                      title={item.card.title}
                      description={item.card.description}
                      img={item.card.img}
                      selectTemplate={(...e) => {
                        return this.selectTemplate(
                          item.key,
                          item.label,
                          item.quillPrefill,
                          item.card.title,
                        )(...e);
                      }}
                    />
                  );
                }

                return '';
              })}
            </div>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}

class TemplateCard extends React.PureComponent {
  state = {};

  render() {
    const { title, description, img, selectTemplate } = this.props;

    return (
      <div className="TemplateCard" onClick={selectTemplate}>
        <div className="img-wrapper">
          <img src={img} alt={title} />
        </div>
        <div className="TemplateCard-details">
          {title}
          <div className="TemplateCard-desc">{description}</div>

          <div className="link">
            <span>Use this template</span>
            <i className="i i-arrow-forward" />
          </div>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({ user: state.session.user }))(WysiwygTemplate);

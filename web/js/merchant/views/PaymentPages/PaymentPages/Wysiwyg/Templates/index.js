import React from 'react';
import { Link } from 'react-router-dom';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import META from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/Templates/meta';
import {
  trackGoBackDashboard,
  trackTemplateSelection,
  trackStartCreation,
} from 'merchant/views/PaymentPages/PaymentPages/ga';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import ShowWhen from 'merchant/components/ShowWhen';

const createYourOwn = {
  card: {
    title: 'Create your Own',
    description: 'Got your own idea? Start with a clean slate.',
    img: '/img/payment_pages/start_from_scratch.jpg',
  },
};

export default class extends React.PureComponent {
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
    const { showCustomTemplate, isBatchPaymentPages } = this.props;
    if (isBatchPaymentPages) {
      this.selectTemplate('custom', null);
      return '';
    }
    return (
      <ModalMask maskClosable={false} class="payment-pages-v2-templates view-1" isBlur={true}>
        <Link class="back-btn" to="/paymentpages/" onClick={trackGoBackDashboard}>
          <i class="i i-chevron-left" />
          Back to Dashboard
        </Link>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div class="slide-in">
              <div class="heading">Choose from the templates</div>
              <p>You can choose one of the templates from below</p>
            </div>

            <div class="TemplateCard-list">
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
                  return (
                    <TemplateCard
                      key={k}
                      title={META[m].card.title}
                      description={META[m].card.description}
                      img={META[m].card.img}
                      selectTemplate={(...e) => {
                        return this.selectTemplate(
                          META[m].key,
                          META[m].label,
                          META[m].quillPrefill,
                          META[m].card.title,
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
      <div class="TemplateCard" onClick={selectTemplate}>
        <div class="img-wrapper">
          <img src={img} alt={title} />
        </div>
        <div class="TemplateCard-details">
          {title}
          <div class="TemplateCard-desc">{description}</div>

          <div class="link">
            <span>Use this template</span>
            <i class="i i-arrow-forward" />
          </div>
        </div>
      </div>
    );
  }
}

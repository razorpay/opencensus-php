import React from 'react';

import { Link } from 'react-router-dom';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import META from './meta';
import track from '../../track';

export default class TemplateSelection extends React.PureComponent {
  selectTemplate = (templateKey) => () => {
    this.props.selectTemplate(templateKey);
    this.props.onClose();
  };

  render() {
    return (
      <ModalMask
        maskClosable={false}
        class={classList('payment-pages-v2-templates', 'view-1', 'PaymentButton--Templates')}
        isBlur={true}
      >
        <Link class="back-btn" to="/paymentbuttons">
          <i class="i i-chevron-left" />
          Back to Dashboard
        </Link>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div class="slide-in">
              <div class="heading">Pick a Button Type</div>
              <p>
                Pick a button which meets your requirements and get a head start on collecting
                payments or you could build your own
              </p>
            </div>

            <div class="TemplateCard-list">
              {Object.keys(META).map((m, k) => {
                if (META.hasOwnProperty(m)) {
                  return (
                    <TemplateCard
                      key={k}
                      title={META[m].card.title}
                      description={META[m].card.description}
                      img={META[m].card.img}
                      selectTemplate={this.selectTemplate(META[m].key)}
                      onMouseEnter={() => track.templateHover(META[m].card.title)}
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
  render() {
    const { title, description, img, selectTemplate, onMouseEnter } = this.props;

    return (
      <div class="TemplateCard" onClick={selectTemplate} onMouseEnter={onMouseEnter}>
        <img src={img} />
        <div class="TemplateCard-details">
          <div class="TemplateCard-title">{title}</div>
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

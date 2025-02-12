import React from 'react';

import { Link } from 'react-router-dom';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { classList, getURLQueryParams } from 'common/utils/rzp-utils';
import META from './meta';
import track from 'merchant/views/PaymentButton/PaymentButton/Create/track';

export default class TemplateSelection extends React.PureComponent {
  selectTemplate = (templateKey) => () => {
    this.props.selectTemplate(templateKey);
    this.props.onClose();
  };

  render() {
    const { redirect } = getURLQueryParams(this.props?.history?.location?.search);
    return (
      <ModalMask
        maskClosable={false}
        className={classList('payment-pages-v2-templates', 'view-1', 'PaymentButton--Templates')}
        isBlur={true}
      >
        <Link className="back-btn" to={redirect ?? '/paymentbuttons'}>
          <i className="i i-chevron-left" />
          Back to Dashboard
        </Link>
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div className="slide-in">
              <div className="heading">Pick a Button Type</div>
              <p>
                Pick a button which meets your requirements and get a head start on collecting
                payments or you could build your own
              </p>
            </div>

            <div className="TemplateCard-list">
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
      <div className="TemplateCard" onClick={selectTemplate} onMouseEnter={onMouseEnter}>
        <img src={img} />
        <div className="TemplateCard-details">
          <div className="TemplateCard-title">{title}</div>
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

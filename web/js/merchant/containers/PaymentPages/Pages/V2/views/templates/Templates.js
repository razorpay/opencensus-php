import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { Link } from 'react-router-dom';

import * as META from './meta';

export default ({ onClose, selectTemplate }) => {
  return (
    <ModalMask
      maskClosable={false}
      class="payment-pages-v2-intro"
      isBlur={true}
    >
      <Link class="back-btn" to="/paymentpages/">
        <i class="i i-chevron-left" />
        Back to Dashboard
      </Link>
      <Modal showCloseBtn={false}>
        <ModalContent>
          <div class="heading">Choose from the templates</div>
          <p>You can choose one of the templates from below</p>

          <div class="TemplateCard-list">
            <TemplateCard
              title="Start from Scratch"
              description="Starting from scratch is fun"
              img=""
              selectTemplate={selectTemplate(null)}
            />
            {Object.keys(META).map((m, k) => {
              if (k === 0) {
                return; // 1st one is _es_module_
              }
              if (META.hasOwnProperty(m)) {
                return (
                  <TemplateCard
                    key={k}
                    title={META[m].title}
                    description={META[m].description}
                    img=""
                    selectTemplate={selectTemplate(META[m].meta)}
                  />
                );
              }
            })}
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

const TemplateCard = ({ title, description, img, selectTemplate }) => (
  <div class="TemplateCard" onClick={selectTemplate}>
    <img src={img} />
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

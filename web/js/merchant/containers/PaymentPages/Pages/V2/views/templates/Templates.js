import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { Link } from 'react-router-dom';

import IntroMask from './IntroMask';

import META from './meta';

export default class extends React.Component {
  state = { isIntroOpened: false };

  selectTemplate = (label, quillPrefill) => {
    return () => {
      this.props.selectTemplate(quillPrefill);

      this.setState({
        templateLabel: label,
        isIntroOpened: true,
      });
    };
  };

  backToTemplate = _ => {
    this.setState({
      isIntroOpened: false,
    });
  };

  render() {
    if (this.state.isIntroOpened) {
      return (
        <IntroMask
          onClose={this.props.onClose}
          backToTemplate={this.backToTemplate}
          templateLabel={this.state.templateLabel}
        />
      );
    }

    return (
      <ModalMask
        maskClosable={false}
        class="payment-pages-v2-templates"
        isBlur={true}
      >
        <Link class="back-btn" to="/paymentpages/">
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
              <TemplateCard
                title="Build your Payment Page"
                description="Create your own Payment Page with custom fields and page settings."
                img="/img/payment_pages/start_from_scratch.jpg"
                selectTemplate={this.selectTemplate(null)}
              />
              {Object.keys(META).map((m, k) => {
                if (META.hasOwnProperty(m)) {
                  return (
                    <TemplateCard
                      key={k}
                      title={META[m].card.title}
                      description={META[m].card.description}
                      img={META[m].card.img}
                      selectTemplate={this.selectTemplate(
                        META[m].label,
                        META[m].quillPrefill
                      )}
                    />
                  );
                }
              })}
            </div>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}

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

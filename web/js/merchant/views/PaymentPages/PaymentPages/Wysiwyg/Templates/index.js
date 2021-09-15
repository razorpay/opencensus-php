import React from 'react';
import { Link } from 'react-router-dom';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import { classList } from 'common/utils/rzp-utils';
import META from './meta';
import {
  trackGoBackDashboard,
  trackGoBackToTemplates,
  trackTemplateSelection,
  trackStartCreation,
} from '../../ga';
import track from '../track/';

const createYourOwn = {
  card: {
    title: 'Create your Own',
    description: 'Got your own idea? Start with a clean slate.',
    img: '/img/payment_pages/start_from_scratch.jpg',
  },
};

export default class extends React.PureComponent {
  state = { isTemplateSelectionOpened: true };

  selectTemplate = (templateKey, label, quillPrefill, title) => {
    return () => {
      this.props.selectTemplate(quillPrefill, templateKey);

      this.setState({
        templateLabel: label,
        isTemplateSelectionOpened: false,
        templateTitle: title,
      });

      track.wysiwyg.selectTemplate(title);
      trackTemplateSelection(title || createYourOwn.card.title);
    };
  };

  backToTemplateView = (_) => {
    this.setState({
      isTemplateSelectionOpened: true,
    });

    trackGoBackToTemplates();
  };

  render() {
    let content = null;

    if (this.state.isTemplateSelectionOpened) {
      content = (
        <React.Fragment key="view-1">
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
                <TemplateCard
                  title={createYourOwn.card.title}
                  description={createYourOwn.card.description}
                  img={createYourOwn.card.img}
                  selectTemplate={this.selectTemplate('custom', null)}
                />
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
        </React.Fragment>
      );
    } else {
      content = (
        <React.Fragment key="view-2">
          <Button.Transparent class="back-btn" onClick={this.backToTemplateView}>
            <i class="i i-chevron-left" />
            Back to Templates
          </Button.Transparent>
          <Modal showCloseBtn={false}>
            <ModalContent>
              <div class="slide-in">
                <div class="heading">Create New {this.state.templateLabel || 'Payment'} Page</div>
                <p>
                  This is how the page will appear to your customers.
                  <br />
                  You can preview and edit the page at the same time!
                </p>
                <Button.Primary
                  onClick={() => {
                    this.props.onClose();
                    trackStartCreation(this.state.templateTitle || createYourOwn.card.title);
                  }}
                  autoFocus
                >
                  Let's Go!
                </Button.Primary>
              </div>
            </ModalContent>
          </Modal>
        </React.Fragment>
      );
    }

    return (
      <ModalMask
        maskClosable={false}
        class={classList(
          'payment-pages-v2-templates',
          this.state.isTemplateSelectionOpened ? 'view-1' : 'view-2',
        )}
        isBlur={true}
      >
        {content}
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
  }
}

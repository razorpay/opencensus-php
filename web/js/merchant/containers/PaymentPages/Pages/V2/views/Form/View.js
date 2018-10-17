import { connect } from 'react-redux';
import EditLayer from '../EditLayer';
import { AmountCreator, AmountField, FormFooter } from './Amount';
import { GenericCreator, GenericField } from './Generic';
import { ModalMask, Modal, ModalContent } from 'component/Modal';

import {
  deleteInSchema,
  updateInSchema,
  addInSchema,
} from 'merchant/modules/wysiwyg';

function offset(el) {
  var rect = el.getBoundingClientRect(),
    scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
    scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  return { top: rect.top + scrollTop, left: rect.left + scrollLeft };
}

const CreatorType = {
  AMOUNT: 'AMOUNT',
  GENERIC: 'GENERIC',
};

@connect(state => ({ FORM_SCHEMA: state.wysiwyg.FORM_SCHEMA }), {
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  state = { activeCreatorType: null };

  openCreator = (e, activeCreatorType) => {
    const parent = document.getElementById('form-section');
    const width = parent.clientWidth + 44 * 2;

    this.creatorStructure = {
      width,
      top: offset(e.target).top,
      left: offset(parent).left - 44,
    };

    this.setState({ activeCreatorType });
  };

  onCreatorClose = _ => {
    this.setState({ activeCreatorType: false });
  };

  onGenericCreatorSubmit = formData => {
    console.log('FORM DATA.....', formData);

    return false;
    this.setState({ activeCreatorType: false });
  };

  onAmountCreatorSubmit = formData => {
    console.log('FORM DATA.....', formData);

    return false;

    // If all fields valid, then
    if (true) {
      // Add / Update FORM_SCHEMA
      this.setState({ activeCreatorType: false });
    }
  };

  render() {
    const FORM_SCHEMA = this.props.FORM_SCHEMA;
    const activeCreatorType = this.state.activeCreatorType;

    let editorContent;

    if (activeCreatorType) {
      const field = {}; // Get data from FORM_SCHEMA if available

      if (activeCreatorType === CreatorType.AMOUNT) {
        editorContent = (
          <AmountCreator
            field={field}
            onClose={this.onCreatorClose}
            onSubmit={this.onAmountCreatorSubmit}
          />
        );
      } else if (activeCreatorType === CreatorType.GENERIC) {
        editorContent = (
          <GenericCreator
            field={field}
            onClose={this.onCreatorClose}
            onSubmit={this.onGenericCreatorSubmit}
          />
        );
      }
    }

    return (
      <React.Fragment>
        {activeCreatorType && (
          <Creator creatorStructure={this.creatorStructure}>
            {editorContent}
          </Creator>
        )}
        <div class="UI-form">
          <AmountField
            onAddAmount={e => this.openCreator(e, CreatorType.AMOUNT)}
          />

          {FORM_SCHEMA.map((field, idx) => {
            let infoTxt = '';
            let isDisabled;
            if (['email', 'phone'].indexOf(field.name) > -1) {
              infoTxt =
                'Email and Phone are fixed fields. You cannot edit them';
              isDisabled = true;
            }

            return (
              <GenericField
                key={idx}
                field={field}
                infoTxt={infoTxt}
                onEditField={
                  !isDisabled
                    ? e => this.openCreator(e, CreatorType.GENERIC)
                    : undefined
                }
              />
            );
          })}
          <EditLayer
            onClick={e => this.openCreator(e, CreatorType.GENERIC)}
            style={{ marginTop: 32, display: 'inline-block' }}
          >
            <span class="btn-link">+ Add new field</span>
          </EditLayer>

          <FormFooter amountToPay={340 * 100} />
        </div>
      </React.Fragment>
    );
  }
}

const Creator = ({ children, creatorStructure }) => {
  return (
    <ModalMask maskClosable={false} class="payment-pages-v2-creator">
      <Modal
        class="animate-appear"
        showCloseBtn={false}
        style={{
          width: creatorStructure.width,
          top: creatorStructure.top,
          left: creatorStructure.left,
          margin: '12px 0 80px',
          transform: 'none',
        }}
      >
        <ModalContent class="paymentlinks-creator">{children}</ModalContent>
      </Modal>
    </ModalMask>
  );
};

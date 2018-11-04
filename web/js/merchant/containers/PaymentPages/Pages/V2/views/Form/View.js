import { connect } from 'react-redux';
import Button from 'component/Button';
import { AmountCreator, AmountField, FormFooter } from './Amount';
import { GenericCreator, GenericField } from './Generic';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { FIELD_TYPES } from './Fields/helpers';

import {
  updateAmount,
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

@connect(state => ({ ...state.wysiwyg }), {
  updateAmount,
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  state = { activeCreatorType: null };

  openCreator = (e, activeCreatorType, activeSchemaIndex) => {
    const parent = document.getElementById('form-section');
    const width = parent.clientWidth + 44 * 2;

    this.creatorStructure = {
      width,
      top: offset(e.target).top,
      left: offset(parent).left - 44,
    };

    this.setState({ activeCreatorType, activeSchemaIndex });
  };

  onCreatorClose = _ => {
    this.setState({ activeCreatorType: false, activeSchemaIndex: null });
  };

  onGenericCreatorSubmit = formData => {
    console.log('FORM DATA.....', formData);

    const { title, field_type, required, description } = formData;

    this.props.updateInSchema({
      field: {
        name: title
          .trim()
          .toLowerCase()
          .split(' ')
          .join('_'),
        title,
        required,
        description,
        ...FIELD_TYPES[field_type].schema,
      },
      index: this.state.activeSchemaIndex,
    });

    this.setState({ activeCreatorType: false });
  };

  onGenericFieldDelete = idx => {
    this.props.deleteInSchema(idx);
  };

  onAmountCreatorSubmit = formData => {
    const { amount, stock, allow_multiple_units } = formData;

    this.props.updateAmount({
      amount: amount || null,
      stock: stock || null,
      settings: {
        allow_multiple_units: allow_multiple_units === '1',
      },
    });

    this.setState({ activeCreatorType: false });
  };

  render() {
    const FORM_SCHEMA = this.props.FORM_SCHEMA;
    const activeCreatorType = this.state.activeCreatorType;
    const { paymentPageEntity } = this.props;

    if (!paymentPageEntity) {
      return null;
    }

    if (paymentPageEntity.id && !paymentPageEntity.title) {
      return (
        <div class="spinner-container">
          <div class="spin-btn large visible" />
        </div>
      );
    }

    let editorContent;

    if (activeCreatorType) {
      if (activeCreatorType === CreatorType.AMOUNT) {
        editorContent = (
          <AmountCreator
            field={paymentPageEntity}
            onClose={this.onCreatorClose}
            onSubmit={this.onAmountCreatorSubmit}
          />
        );
      } else if (activeCreatorType === CreatorType.GENERIC) {
        editorContent = (
          <GenericCreator
            field={FORM_SCHEMA[this.state.activeSchemaIndex] || {}}
            selfIndex={this.state.activeSchemaIndex}
            allFieldsLabelList={FORM_SCHEMA.map(f => f.title)}
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
            paymentPageEntity={paymentPageEntity}
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
                key={field.name}
                onFieldDelete={this.onGenericFieldDelete}
                selfIndex={idx}
                field={field}
                infoTxt={infoTxt}
                isRemovable={['email', 'phone'].indexOf(field.name) === -1} // email and phone are non-removable from FE
                onEditField={
                  !isDisabled
                    ? e => this.openCreator(e, CreatorType.GENERIC, idx)
                    : undefined
                }
              />
            );
          })}
          <Button.Transparent
            class="btn-link"
            onClick={e => this.openCreator(e, CreatorType.GENERIC)}
            style={{ marginTop: 32, display: 'inline-block' }}
          >
            + Add new field
          </Button.Transparent>

          <FormFooter amountToPay={paymentPageEntity.amount} />
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

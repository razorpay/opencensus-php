import { connect } from 'react-redux';
import Button from 'component/Button';
import { AmountCreator, AmountField, FormFooter } from './Amount';
import { GenericCreator, GenericField } from './Generic';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { constructFieldSchema } from '../UDF_Fields/V2';

import {
  updateData,
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
  updateData,
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  state = { activeCreatorType: null };

  componentWillReceiveProps(nextProps) {
    if (this.props.payment_page_id !== nextProps.payment_page_id) {
      this.onCreatorClose();
    }
  }

  openCreator = (e, activeCreatorType, activeSchemaIndex) => {
    const parent = document.getElementById('form-section');
    const width = parent.clientWidth + 44 * 2;

    this.creatorStructure = {
      width,
      left: offset(parent).left - 44,
    };

    this.setState({ activeCreatorType, activeSchemaIndex });
  };

  onCreatorClose = _ => {
    this.setState({ activeCreatorType: false, activeSchemaIndex: null });
  };

  onGenericCreatorSubmit = formData => {
    // console.log('FORM DATA.....', formData);
    const fieldSchema = constructFieldSchema(formData);
    // console.log('FIELD SCHEMA...', fieldSchema);
    if (
      !fieldSchema ||
      (fieldSchema.enum && (!formData.enum || !formData.enum.length))
    ) {
      throw 'Invalid field data';
    }

    if (fieldSchema.enum) {
      fieldSchema.enum = formData.enum.concat();
    }

    this.props.updateInSchema({
      field: fieldSchema,
      index: this.state.activeSchemaIndex,
    });

    this.setState({ activeCreatorType: false });
  };

  onGenericFieldDelete = idx => {
    this.onCreatorClose();
    this.props.deleteInSchema(idx);
  };

  onAmountCreatorSubmit = formData => {
    const { currency, amount, quantity, allow_multiple_units } = formData;

    this.props.updateData({
      currency,
      amount: amount || null,
      quantity: quantity || null,
      settings: {
        allow_multiple_units: !!allow_multiple_units,
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

    if (
      paymentPageEntity.id &&
      typeof paymentPageEntity.title === 'undefined'
    ) {
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
        const field = FORM_SCHEMA[this.state.activeSchemaIndex] || {};
        let isRemovable = true;
        if (['email', 'phone'].indexOf(field.name) > -1) {
          isRemovable = false;
        }

        editorContent = (
          <GenericCreator
            field={field}
            selfIndex={this.state.activeSchemaIndex}
            allFieldsLabelList={FORM_SCHEMA.map(f => f.title)}
            onClose={this.onCreatorClose}
            onSubmit={this.onGenericCreatorSubmit}
            onFieldDelete={isRemovable ? this.onGenericFieldDelete : undefined}
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
              infoTxt = 'This field cannot be removed';
              isDisabled = true;
            }

            return (
              <GenericField
                key={field.name}
                field={field}
                infoTxt={infoTxt}
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
          top: '50%',
          left: creatorStructure.left,
          margin: '12px 0 0',
          transform: 'translateY(-50%)',
        }}
      >
        <ModalContent class="paymentlinks-creator">{children}</ModalContent>
      </Modal>
    </ModalMask>
  );
};

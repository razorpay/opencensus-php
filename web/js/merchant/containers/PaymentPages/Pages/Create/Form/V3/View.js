import { connect } from 'react-redux';
import AmountDisplayField from './Amount/AmountDisplayField';
import UDFDisplayField from './UDF/UDFDisplayField';
import AddUDFButton from './UDF/AddUDFButton';
import AddAmountButton from './Amount/AddAmountButton';

import {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  isFormItemOfTypeAmount,
  reorderFormItems,
} from 'merchant/modules/wysiwyg';
import { constructFieldSchema } from '../UDF_Fields/V3';
import { constructAmountField } from '../Amount_Fields/V3';
import { sortableContainer, sortableElement } from 'react-sortable-hoc';

import { arrayMove } from 'common/util';

const Sortable_UDFDisplayField = sortableElement(UDFDisplayField);
const Sortable_AmountDisplayField = sortableElement(AmountDisplayField);

@sortableContainer
class SortableFormItemsList extends React.Component {
  render() {
    const {
      FORM_ITEMS,
      isListSorting,
      validateSameTitleExists,
      onDeleteUDFItem,
      onDeleteAmountItem,
      onSubmitUDFField,
      onSubmitAmountField,
    } = this.props;

    return (
      <div class="FormItems">
        {FORM_ITEMS.map((fi, idx) => {
          if (isFormItemOfTypeAmount(fi)) {
            return (
              <Sortable_AmountDisplayField
                key={fi.item.title}
                index={idx}
                field={fi}
                isListSorting={isListSorting}
                onDeleteFormItem={onDeleteAmountItem}
                onSubmitAmountField={onSubmitAmountField}
                validateSameTitleExists={validateSameTitleExists}
              />
            );
          } else {
            return (
              <Sortable_UDFDisplayField
                key={fi.name}
                index={idx}
                field={fi}
                isListSorting={isListSorting}
                onDeleteFormItem={onDeleteUDFItem}
                onSubmitUDFField={onSubmitUDFField}
                validateSameTitleExists={validateSameTitleExists}
              />
            );
          }
        })}
      </div>
    );
  }
}

@connect(state => ({ ...state.wysiwyg }), {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  reorderFormItems,
})
export default class View extends React.PureComponent {
  state = {
    isListSorting: false,
    hasAmountItem: this.props.payment_page_id
      ? this.props.paymentPageEntity.payment_page_items.length
      : 0,
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.payment_page_id !== nextProps.payment_page_id) {
      // TODO: Update state.hasAmountItem = false if nextProps. payment_page_id doesn't exist
      // this.onCreatorClose(); // TODO: Important controller point to close all the modals
    }
  }

  onSubmitAmountField = (formData, indexInFormItems) => {
    const amountItem = constructAmountField(formData);

    this.props.updateInFormItems({
      formItem: amountItem,
      index: indexInFormItems,
    });

    this.setState({
      hasAmountItem: this.state.hasAmountItem + 1,
    });
  };

  onDeleteAmountItem = indexInFormItems => {
    this.props.deleteInFormItems(indexInFormItems);

    this.setState({
      hasAmountItem: this.state.hasAmountItem - 1,
    });
  };

  onDeleteUDFItem = indexInFormItems => {
    this.props.deleteInFormItems(indexInFormItems);
  };

  onSubmitUDFField = (formData, indexInFormItems) => {
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

    this.props.updateInFormItems({
      formItem: fieldSchema,
      index: indexInFormItems,
    });
  };

  validateSameTitleExists = (title, fieldSelfIndex) => {
    const allFieldsTitles = this.props.FORM_ITEMS.map(f => {
      return isFormItemOfTypeAmount(f) ? f.item.title : f.title;
    });

    const sameTitleIndex = allFieldsTitles.indexOf(title);

    if (sameTitleIndex > -1 && sameTitleIndex !== fieldSelfIndex) {
      return true;
    }
  };

  onSortStart = _ => {
    this.setState({
      isListSorting: true,
    });
  };

  onSortEnd = _ => {
    this.setState({
      isListSorting: false,
    });

    this.props.reorderFormItems(_);
  };

  render() {
    const { paymentPageEntity, FORM_ITEMS } = this.props;

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

    return (
      <React.Fragment>
        <div class="UI-form">
          {!this.state.hasAmountItem && (
            <div className="Field Field-dummyAmount">
              <div className="Field-label" style={{ opacity: 0.6 }}>
                Amount
              </div>

              <div className="Field-content">
                <AddAmountButton
                  field={{ item: { title: 'Amount' } }}
                  onDeleteFormItem={this.onDeleteFormItem}
                  onSubmitAmountField={formData =>
                    this.onSubmitAmountField(formData, -1)
                  } /*Added in the starting of form Items*/
                  validateSameTitleExists={this.validateSameTitleExists}
                />
              </div>
            </div>
          )}

          <SortableFormItemsList
            lockAxis="y"
            useDragHandle
            lockToContainerEdges
            helperClass="CreatorManager"
            helperContainer={document.getElementById(
              'draggableElementsContainer'
            )}
            onSortEnd={this.onSortEnd}
            onSortStart={this.onSortStart}
            isListSorting={this.state.isListSorting}
            FORM_ITEMS={FORM_ITEMS}
            onDeleteUDFItem={this.onDeleteUDFItem}
            onDeleteAmountItem={this.onDeleteAmountItem}
            onSubmitAmountField={this.onSubmitAmountField}
            onSubmitUDFField={this.onSubmitUDFField}
            validateSameTitleExists={this.validateSameTitleExists}
          />

          <div class="Field" style={{ margin: '32px 0 -21px' }}>
            <div class="Field-label" style={{ opacity: 0.6 }}>
              Add new
            </div>

            <div class="Field-content">
              <AddUDFButton
                onDeleteFormItem={this.onDeleteUDFItem}
                onSubmitUDFField={this.onSubmitUDFField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
              <AddAmountButton
                onDeleteFormItem={this.onDeleteAmountItem}
                onSubmitAmountField={this.onSubmitAmountField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
            </div>
          </div>

          <FormFooter amountToPay={paymentPageEntity.amount} />

          <div id="draggableElementsContainer" />
        </div>
      </React.Fragment>
    );
  }
}

const FormFooter = ({ amountToPay }) => (
  <div id="form-footer">
    <img
      id="fin-logo"
      alt="pay-methods"
      src="https://cdn.razorpay.com/static/assets/upi_visa_mc_ae_pc.png"
    />
  </div>
);

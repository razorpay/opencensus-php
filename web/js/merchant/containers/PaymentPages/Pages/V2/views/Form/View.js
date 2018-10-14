import { connect } from 'react-redux';
import EditLayer from '../EditLayer';
import { AmountField, FormFooter } from './Amount';
import { GenericCreator, GenericField } from './Generic';

import {
  deleteInSchema,
  updateInSchema,
  addInSchema,
} from 'merchant/modules/wysiwyg';

@connect(state => ({ FORM_SCHEMA: state.wysiwyg.FORM_SCHEMA }), {
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  handleClick = _ => {};
  handleAddNewField = _ => {};
  handleAddAmount = _ => {};

  render() {
    const FORM_SCHEMA = this.props.FORM_SCHEMA;

    return (
      <div class="UI-form">
        <AmountField handleAddAmount={this.handleAddAmount} />

        {FORM_SCHEMA.map((field, idx) => {
          let infoTxt = '';
          let isDisabled;
          if (['email', 'phone'].indexOf(field.name) > -1) {
            infoTxt = 'Email and Phone are fixed fields. You cannot edit them';
            isDisabled = true;
          }

          return (
            <GenericField
              key={idx}
              field={field}
              infoTxt={infoTxt}
              handleClick={!isDisabled ? this.handleClick : undefined}
            />
          );
        })}
        <EditLayer
          onClick={this.handleAddNewField}
          style={{ marginTop: 32, display: 'inline-block' }}
        >
          <span class="btn-link">+ Add new field</span>
        </EditLayer>

        <FormFooter amountToPay={340 * 100} />
      </div>
    );
  }
}

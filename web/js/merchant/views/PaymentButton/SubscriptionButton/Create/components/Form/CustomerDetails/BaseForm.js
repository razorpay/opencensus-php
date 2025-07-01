import React from "react";
import Alert from 'common/new-ui/Alert';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import EditorModal from '../components/EditorModal';
import InputDropdown from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/InputDropdown';
import FieldOptionsDropdown, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';

import { classList } from 'common/utils/rzp-utils';
import {
  getFieldTypes,
  mapFieldToIndex,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
// import track from '../../../track';

const udfFieldTypeOptionComponent = ({ option }) => (
  <div>
    <i className={classList('i', option.icon && 'i-' + option.icon)} />
    <span>{option.label}</span>
    <i className="i i-check" />
  </div>
);

export default class BaseForm extends React.Component {
  constructor(props) {
    super(props);

    this.fieldTypeOptions = getFieldTypes(true);

    const { field, selectedOptionInFieldTypes } = this.props;

    this.state = {
      selectedOptionInFieldTypes,
      hasDescription: !!field.description,
      isRequired: field.hasOwnProperty('required') ? field.required : true, // NOTE: All new fields are added as required
      enumOptions: field.hasOwnProperty('enum') ? field.enum : null,
      disableSubmit: !field.title,
    };
  }

  toggleSubmitBtn = () => {
    let disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    if (this.state.enumOptions && !this.state.enumOptions.length) {
      disableSubmit = true;
    }

    this.setState({ disableSubmit });
  };

  handleSubmit = (formData) => {
    const newField = {
      title: formData.title,
      description: formData.description,
      required: formData.required,
      field_type: formData.field_type,
    };

    if (this.state.enumOptions) {
      newField.enum = this.state.enumOptions;
    }

    this.props.onSubmit(newField);

    this.props.handleClose();
  };

  handleChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  handleToggleMakeOptional = () => {
    this.setState(
      {
        isRequired: !this.state.isRequired,
      },
      () => {
        // track.lj.trackCustomerScreenToggleMakeOptional(this.state.isRequired);
      },
    );
  };

  handleToggleAddDescription = () => {
    this.setState(
      {
        hasDescription: !this.state.hasDescription,
      },
      () => {
        // track.lj.trackCustomerScreenDescriptionField(this.state.hasDescription);
      },
    );
  };

  onChangeFieldType = (option) => {
    this.setState({
      selectedOptionInFieldTypes: option,
      enumOptions: option.schema.hasOwnProperty('enum') ? [] : null,
    });

    // track.lj.trackCustomerScreenFieldType(option);
  };

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ enumOptions: trimmedEnums });

    setTimeout(this.toggleSubmitBtn);
  };

  get indexInUDFDropdown() {
    const { selectedOptionInFieldTypes } = this.state;
    const currentFieldSchema = selectedOptionInFieldTypes.schema;

    return mapFieldToIndex(currentFieldSchema);
  }

  get additionalOptionsButton() {
    const { indexInOrder, isFieldForcedRequired, handleDeleteField } = this.props;
    const { isRequired, hasDescription } = this.state;

    return (
      <FieldOptionsDropdown
        trigger={
          <Button.Transparent
          // onClick={track.lj.trackCustomerScreenInputFieldMoreOptions}
          >
            <i className="i i-ellipsis-v" />
          </Button.Transparent>
        }
      >
        {!isFieldForcedRequired && (
          <OptionsItem isSelected={!isRequired}>
            <div onClick={this.handleToggleMakeOptional}>
              <i className="i i-optional_mark" />
              Optional Field
            </div>
          </OptionsItem>
        )}

        <OptionsItem isSelected={!!hasDescription}>
          <div onClick={this.handleToggleAddDescription}>
            <i className="i i-sort i-fix-sort" />
            {hasDescription ? 'Remove Description' : 'Add Description'}
          </div>
        </OptionsItem>

        {typeof indexInOrder !== 'undefined' && handleDeleteField && (
          <OptionsItem>
            <div className="OptionsDropdown-item--delete" onClick={handleDeleteField}>
              <i className="i i-delete" />
              <div>Delete Field</div>
            </div>
          </OptionsItem>
        )}
      </FieldOptionsDropdown>
    );
  }

  get formFooter() {
    const { disableSubmit } = this.state;

    return (
      <div className="CreatorModal-BaseForm-footer">
        <button
          type="button"
          className="cancel-btn Button--transparent Button"
          onClick={() => {
            this.props.handleClose();

            // track.lj.trackCustomerScreenCancelFieldChanges();
          }}
        >
          <span>&times;</span>
          Cancel
        </button>

        <button type="submit" className="save-btn Button--transparent Button" disabled={disableSubmit}>
          <span className="icon i-check" />
          Save
        </button>
      </div>
    );
  }

  setRefForm = (el) => (this.formEl = el);

  render() {
    const { field, isFieldForcedRequired, selectedOptionInFieldTypes } = this.props;

    // Field is taken from state and not from props, bcoz user might change field_type from InputDropdown
    const { isRequired, hasDescription, enumOptions } = this.state;

    return (
      <EditorModal className="CreatorModal-BaseForm" overElement allowScroll>
        <Form onSubmit={this.handleSubmit} onChange={this.handleChange} setRef={this.setRefForm}>
          <InputDropdown
            label="Field Type"
            className="Input--vTop"
            description={!isRequired ? 'Optional Field' : ''}
            disabled={isFieldForcedRequired}
            dropdownElementClass="ps-in-modal Input-el-PaymentButtonForm"
            placeholder="Select Button Theme"
            options={this.fieldTypeOptions}
            optionLabelPath="label"
            optionValuePath="schema"
            optionComponent={udfFieldTypeOptionComponent}
            defaultValue={selectedOptionInFieldTypes.schema}
            onChange={this.onChangeFieldType}
          />

          {/* TODO: These 2 hidden fields can be removed and relied upon through state */}
          <input name="field_type" value={this.indexInUDFDropdown} hidden readOnly />
          <input name="required" value={isRequired | 0} hidden readOnly />

          <Input.Group label="Field Label" className="Input--vTop" required>
            <Input
              name="title"
              className="Input--vTop"
              placeholder="Enter field label"
              defaultValue={field.title}
              autoFocus
              validator={(val) => {
                if (!val) {
                  return 'Field label is required';
                }

                const regex = new RegExp(`^[0-9a-zA-Z ]+$`, 'i');

                if (!regex.test(val)) {
                  return 'Please enter valid value';
                }

                if (!isNaN(val)) {
                  return 'Field label must have at least 1 character';
                }

                if (this.props.validateSameTitleExists(val, this.props.indexInOrder)) {
                  return 'Field label cannot be same as other field';
                }
              }}
            />

            {hasDescription && (
              <Input.TextareaAutoResize
                className="Input--description"
                name="description"
                placeholder="Enter field description"
                defaultValue={field.description}
                validator={(val) => {
                  if (val && val.length > 128) {
                    return 'Field description cannot be more than 128 characters';
                  }
                }}
              />
            )}

            {enumOptions && (
              <React.Fragment>
                <br />
                <Input.Group label="Dropdown Values">
                  <Input.EnumList
                    className="dropdown-options"
                    onChange={this.onChangeEnumList}
                    defaultValue={enumOptions.length ? enumOptions : ['']}
                  />
                </Input.Group>
              </React.Fragment>
            )}
          </Input.Group>

          {this.formFooter}
        </Form>

        {isFieldForcedRequired && (
          <Alert.Warning>
            <b>Mandatory</b> {field.name} field to be filled by customers. This field cannot be
            deleted.
          </Alert.Warning>
        )}

        {this.additionalOptionsButton}
      </EditorModal>
    );
  }
}

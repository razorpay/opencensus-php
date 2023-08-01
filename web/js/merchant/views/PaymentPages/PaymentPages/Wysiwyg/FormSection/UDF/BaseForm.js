import React from 'react';
import { connect } from 'react-redux';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import FieldOptionsDropdownWrapper, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';
import { classList } from 'common/utils/rzp-utils';
import { mapFieldToIndex } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  BATCH_UPLOAD_MSG,
  FILLED_BY_CUSTOMER,
  SEC_REF_ID,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { FIXED_FIELDS } from './helpers/preAddedFields';

@connect((state) => ({ countryCode: state.session.user.merchant.country_code }))
export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const fieldSchema = props.field;

    this.state = {
      disableSubmit: !fieldSchema.title, // Any required field is valid to do init, like 'name', 'title', 'type'
      hasDescription: !!fieldSchema.description,
      mirrorDisplayTitle: fieldSchema.title,
      isRequired: typeof fieldSchema.required !== 'undefined' ? !!fieldSchema.required : true, // NOTE: By default all fields are to be set as required
      isFieldEnum: !!fieldSchema.enum,
      enum: fieldSchema.hasOwnProperty('enum') ? fieldSchema.enum : undefined,
      isSecondaryRefId: fieldSchema?.name?.indexOf(SEC_REF_ID) > -1 ?? false,
    };

    this.fieldIndexInOptions =
      props.fieldIndexInOptions || mapFieldToIndex(fieldSchema, props.countryCode);
  }

  onChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    if (this.state.isFieldEnum && (!this.state.enum || !this.state.enum.length)) {
      disableSubmit = true;
    }

    this.setState({ disableSubmit });
  };

  onSaveForm = (formData) => {
    // Assuming this.state.enum.length > 1 always otherwise toggleSubmitBtn will handle
    if (this.state.enum) {
      formData.enum = this.state.enum;
    }

    this.props.onSaveForm(formData);
  };

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ enum: trimmedEnums });

    setTimeout(this.toggleSubmitBtn);
  };

  toggleSecondaryRefId = (_) => {
    this.setState((prevState) => {
      return {
        isSecondaryRefId: !prevState.isSecondaryRefId,
      };
    });
  };

  toggleDescriptionField = (_) => {
    this.setState((prevState) => {
      return {
        hasDescription: !prevState.hasDescription,
      };
    });
  };

  toggleOptional = (_) => {
    this.setState((prevState) => {
      return {
        isRequired: !prevState.isRequired,
      };
    });
  };

  onInputTitle = ({ target }) => {
    this.setState({
      mirrorDisplayTitle: target.value,
    });
  };

  setRefForm = (el) => (this.formEl = el);

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
      onDeleteField,
      isFieldForcedRequired,
      isShiprocket,
      isLabelDisabled,
      isBatchPaymentPages,
      isPrimaryField,
    } = this.props;

    const { isRequired, hasDescription, disableSubmit, mirrorDisplayTitle, isSecondaryRefId } =
      this.state;
    const placeHolder = isBatchPaymentPages ? BATCH_UPLOAD_MSG : FILLED_BY_CUSTOMER;
    let _RepresentationEl = <input class="Field-el" placeholder={placeHolder} disabled />;
    let _RepresentationClass = '';
    if (field.options && field.options.cmp === 'textarea') {
      _RepresentationEl = <textarea class="Field-el" placeholder={FILLED_BY_CUSTOMER} disabled />;
      _RepresentationClass = 'Field--textarea';
    } else if (field.enum) {
      _RepresentationEl = (
        <select class="Field-el" disabled>
          <option>To be selected by customer</option>
        </select>
      );
      _RepresentationClass = 'Field--select';
    }

    const shouldShowSecRefIDOption =
      isBatchPaymentPages &&
      FIXED_FIELDS.secondaryRefId.name !== field.name && // As first secondary referece id field is mandatory and is created by default not providing the option this option.
      !isPrimaryField; // Not providing option for primary reference id.

    return (
      <Form setRef={this.setRefForm} onChange={this.onChange} onSubmit={this.onSaveForm}>
        <Input.TextareaAutoResize
          class="Input--title"
          name="title"
          defaultValue={field.title}
          maxLength="60"
          placeholder="Enter field label"
          onInput={this.onInputTitle}
          autoRender
          disabled={isShiprocket || isLabelDisabled}
          validator={(val) => {
            if (!val) {
              return 'Field title is required';
            }

            const regex = new RegExp(`^[0-9a-zA-Z ]+$`, 'i');

            if (!regex.test(val)) {
              return 'Please enter valid value';
            }

            if (!isNaN(val)) {
              return 'Field title must have at least 1 character';
            }

            if (validateSameTitleExists(val, selfIndex)) {
              return 'Field title cannot be same as other field';
            }
            return '';
          }}
          autoFocus
        >
          <div class={classList('Field Field--mirrorDisplay', isRequired && 'Field--required')}>
            <span class="mirror-title">{mirrorDisplayTitle}</span>
            {mirrorDisplayTitle && !isRequired && <div class="text-optional">(Optional)</div>}
          </div>
        </Input.TextareaAutoResize>

        <input name="field_type" value={this.fieldIndexInOptions} hidden readOnly />
        <input name="required" value={Number(isRequired)} hidden readOnly />
        <ShowWhen additionalCondition={() => isBatchPaymentPages}>
          <input name="sec__ref__id" value={Number(isSecondaryRefId)} hidden readOnly />
        </ShowWhen>

        <div class={classList('Field--representation', _RepresentationClass)}>
          <div class="Field-wrapper placeholder-field">{_RepresentationEl}</div>

          {this.state.isFieldEnum && (
            <Input.EnumList
              class="dropdown-options"
              onChange={this.onChangeEnumList}
              defaultValue={field.enum.length ? field.enum : ['']}
            />
          )}

          {hasDescription && (
            <Input.TextareaAutoResize
              class="Input--description"
              name="description"
              placeholder="Enter description"
              defaultValue={field.description}
              validator={(val) => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
                return '';
              }}
              autoFocus
            />
          )}
        </div>

        <FieldOptionsDropdownWrapper
          trigger={
            <Button.Transparent data-testid="dropdown-trigger">
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <ShowWhen additionalCondition={() => shouldShowSecRefIDOption}>
            <OptionsItem isSelected={isSecondaryRefId}>
              <div onClick={this.toggleSecondaryRefId}>
                <i className="i i-optional_mark" />
                Select as Secondary Reference ID
              </div>
            </OptionsItem>
          </ShowWhen>

          {!isFieldForcedRequired && (
            <OptionsItem isSelected={!isRequired}>
              <div onClick={this.toggleOptional}>
                <i class="i i-optional_mark" />
                Optional Field
              </div>
            </OptionsItem>
          )}

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-sort i-fix-sort" />
              {this.state.hasDescription ? 'Remove Description' : 'Add Description'}
            </div>
          </OptionsItem>

          {typeof selfIndex !== 'undefined' && onDeleteField && (
            <OptionsItem>
              <div class="OptionsDropdown-item--delete" onClick={onDeleteField}>
                <i class="i i-delete" />
                <div>Delete Field</div>
              </div>
            </OptionsItem>
          )}
        </FieldOptionsDropdownWrapper>

        <Button.Transparent
          class="base-form-side-btn base-form-cancel"
          type="button"
          onClick={onCloseForm}
        >
          <span>&times;</span>
          Cancel
        </Button.Transparent>

        <Button.Transparent
          class="base-form-side-btn base-form-save"
          type="submit"
          disabled={disableSubmit}
        >
          <span class="icon i-check" />
          Save
        </Button.Transparent>
      </Form>
    );
  }
}

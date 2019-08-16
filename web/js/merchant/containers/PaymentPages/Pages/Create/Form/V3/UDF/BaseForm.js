import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';
import { mapFieldToIndex } from '../../UDF_Fields/V3';
import FieldOptionsDropdown, { OptionsItem } from '../../FieldOptionsDropdown';

export default class BaseForm extends React.PureComponent {
  constructor(props) {
    super(props);

    const fieldSchema = props.field;

    this.state = {
      disableSubmit: !fieldSchema.title, // Any required field is valid to do init, like 'name', 'title', 'type'
      hasDescription: !!fieldSchema.description,
      mirrorDisplayTitle: fieldSchema.title,
      isRequired: !!fieldSchema.required || true, // NOTE: By default all fields are required
      isFieldEnum: !!fieldSchema.enum,
      enum: fieldSchema.hasOwnProperty('enum') ? fieldSchema.enum : undefined,
    };

    this.fieldIndexInOptions =
      props.fieldIndexInOptions || mapFieldToIndex(fieldSchema);
  }

  onChange = ({ target }) => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  toggleSubmitBtn = () => {
    const form = this.formEl;
    let disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    if (
      this.state.isFieldEnum &&
      (!this.state.enum || !this.state.enum.length)
    ) {
      disableSubmit = true;
    }

    this.setState({ disableSubmit });
  };

  onSaveField = formData => {
    // Assuming this.state.enum.length > 1 always otherwise toggleSubmitBtn will handle
    if (this.state.enum) {
      formData.enum = this.state.enum;
    }

    this.props.onSaveField(formData);
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

  toggleDescriptionField = _ => {
    this.setState({
      hasDescription: !this.state.hasDescription,
    });
  };

  toggleOptional = _ => {
    this.setState({
      isRequired: !this.state.isRequired,
    });
  };

  onDeleteField = _ => {
    this.props.onDeleteField();
  };

  onInputTitle = ({ target }) => {
    this.setState({
      mirrorDisplayTitle: target.value,
    });
  };

  setRefForm = el => (this.formEl = el);

  render() {
    const {
      field,
      selfIndex,
      validateSameTitleExists,
      onCloseForm,
      onDeleteField,
    } = this.props;

    const {
      isRequired,
      hasDescription,
      disableSubmit,
      mirrorDisplayTitle,
    } = this.state;

    console.log('FIELD...', field);

    let _RepresentationEl = (
        <input
          className="Field-el"
          placeholder="To be filled by customer"
          disabled
        />
      ),
      _RepresentationClass = '';

    if (field.options && field.options.cmp === 'textarea') {
      _RepresentationEl = (
        <textarea
          class="Field-el"
          placeholder="To be filled by customer"
          disabled
        />
      );
      _RepresentationClass = 'Field--textarea';
    } else if (field.enum) {
      _RepresentationEl = (
        <select className="Field-el" disabled>
          <option>To be selected by customer</option>
        </select>
      );
      _RepresentationClass = 'Field--select';
    }

    return (
      <Form
        setRef={this.setRefForm}
        onChange={this.onChange}
        onSubmit={this.onSaveField}
      >
        <Input.TextareaAutoResize
          class="Input--title"
          name="title"
          defaultValue={field.title}
          placeholder="Enter field title"
          pattern="^[0-9a-zA-Z ]+"
          onInput={this.onInputTitle}
          validator={function(val) {
            if (!val) {
              return 'Field title is required';
            }

            if (!isNaN(val)) {
              return 'Field title must have atleast 1 character';
            }

            if (validateSameTitleExists(val, selfIndex)) {
              return 'Field title cannot be same as other field';
            }
          }}
          autoFocus
        >
          <div
            class={classList(
              'Field Field--mirrorDisplay',
              isRequired && 'Field--required'
            )}
          >
            <span class="mirror-title">{mirrorDisplayTitle}</span>
            {mirrorDisplayTitle && <span className="symbol--red">*</span>}
          </div>
        </Input.TextareaAutoResize>

        <input
          name="field_type"
          value={this.fieldIndexInOptions}
          hidden
          readOnly
        />
        <input name="required" value={isRequired | 0} hidden readOnly />

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
              validator={val => {
                if (val && val.length > 128) {
                  return 'Field description cannot be more than 128 characters';
                }
              }}
              autoFocus
            />
          )}
        </div>

        <FieldOptionsDropdown
          trigger={
            <Button.Transparent>
              <i class="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <OptionsItem isSelected={!this.state.isRequired}>
            <div onClick={this.toggleOptional}>
              <i class="i i-info-circle" />
              Optional
            </div>
          </OptionsItem>

          <OptionsItem isSelected={!!this.state.hasDescription}>
            <div onClick={this.toggleDescriptionField}>
              <i class="i i-info-circle" />
              {this.state.hasDescription ? 'Remove' : 'Add'} Description
            </div>
          </OptionsItem>

          {!!selfIndex &&
            onDeleteField && (
              <OptionsItem>
                <div onClick={this.onDeleteField}>
                  <i class="i i-delete" />
                  Delete Field
                </div>
              </OptionsItem>
            )}
        </FieldOptionsDropdown>

        <Button.Transparent
          class="base-form-side-btn base-form-close"
          type="button"
          onClick={onCloseForm}
        >
          <span>&times;</span>
          Close
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

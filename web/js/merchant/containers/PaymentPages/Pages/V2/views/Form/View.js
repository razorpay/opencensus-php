import { connect } from 'react-redux';
import { classList } from 'common/util';
import EditLayer from '../EditLayer';
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

  render() {
    const FORM_SCHEMA = this.props.FORM_SCHEMA;

    return (
      <div class="UI-form">
        {FORM_SCHEMA.map((field, idx) => {
          let infoTxt = '';
          let isDisabled;
          if (['email', 'phone'].indexOf(field.name) > -1) {
            infoTxt = 'Email and Phone are fixed fields. You cannot edit them';
            isDisabled = true;
          }

          return (
            <PlaceholderField
              key={idx}
              field={field}
              infoTxt={infoTxt}
              handleClick={!isDisabled ? this.handleClick : undefined}
            />
          );
        })}
        <div id="form-footer">
          <img
            id="fin-logo"
            alt="pay-methods"
            src="https://cdn.razorpay.com/static/assets/pay_methods_branding.png"
          />
          <div class="btn" type="submit" disabled>
            <div>
              <span>Pay ₹ 45.33</span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
              >
                <path d="M0 0h24v24H0z" fill="none" />
                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const PlaceholderField = ({ field, handleClick, infoTxt }) => {
  return (
    <EditLayer
      customClass={classList(
        'Field Field--disabled',
        field.required && 'Field--required',
        !handleClick && 'disable-hover'
      )}
      onClick={handleClick}
      infoTxt={infoTxt}
    >
      <div class="Field-label">
        {field.title}
        {field.required && <span class="symbol--red">*</span>}
      </div>
      <div class="Field-content">
        <div
          class={classList(
            'Field-wrapper',
            field._type && 'Field-wrapper--' + field_type
          )}
        >
          <input class="Field-el" disabled />
        </div>
        {field.description && (
          <div class="Field-description">{field.description}</div>
        )}
      </div>
    </EditLayer>
  );
};

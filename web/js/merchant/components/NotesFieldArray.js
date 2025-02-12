import { Field } from 'redux-form';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import InputField from 'common/ui/Forms/InputField';
import { isPresent } from 'common/utils/rzp-utils';

const required = (index) => {
  return (currentValue, allProps) => {
    if (!allProps.notes[index]) {
      return;
    }

    const key = allProps.notes[index].key;
    const value = allProps.notes[index].value;
    if (isPresent(value) && !isPresent(key)) {
      // eslint-disable-next-line consistent-return
      return 'Key is required';
    }
  };
};

export default ({
  fields,
  nonEditableUptilIndex = -1,
  showLinkedAccountOpt,
  customAddMsg = null,
  onAddNotesClick = () => {},
}) => {
  return (
    <ul className="list-unstyled notes">
      {fields.map((note, index) => {
        return (
          <li className="note" key={index}>
            <div className="key">
              {index > nonEditableUptilIndex && (
                <i className="i i-close" onClick={() => fields.remove(index)} />
              )}

              <Field
                name={`notes[${index}][key]`}
                component={InputField}
                className="form-control"
                placeholder="Title (key)"
                validate={required(index)}
                disabled={index <= nonEditableUptilIndex}
              />
            </div>

            <div className="value">
              <Field
                name={`notes[${index}][value]`}
                component={AutoResizeTextarea}
                rows="2"
                className="form-control"
                placeholder="Description (value)"
                disabled={index <= nonEditableUptilIndex}
              />
            </div>
            {showLinkedAccountOpt && (
              <div className="checkbox rzpCheckbox">
                <Field
                  name={`notes[${index}][also_linked_account]`}
                  id={`notes[${index}][also_linked_account]`}
                  component="input"
                  type="checkbox"
                />
                <label
                  htmlFor={`notes[${index}][also_linked_account]`}
                  className="icon i-check"
                  style={{ lineHeight: '18px' }}
                >
                  <span>Show note to Linked Account</span>
                </label>
              </div>
            )}
          </li>
        );
      })}

      {fields.length < 10 ? (
        <li>
          <button
            className="btn btn-link add-note"
            type="button"
            onClick={() => {
              fields.push({});
              onAddNotesClick();
            }}
          >
            {customAddMsg || 'Add Internal Note'}
          </button>
        </li>
      ) : null}
    </ul>
  );
};

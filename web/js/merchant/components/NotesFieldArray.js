import { Field } from 'redux-form';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import InputField from 'rzp/ui/Forms/InputField';
import { isPresent } from 'rzp/utils/rzp-utils';

const required = index => {
  return (currentValue, allProps) => {
    if (!allProps.notes[index]) {
      return;
    }

    let key = allProps.notes[index].key;
    let value = allProps.notes[index].value;
    if (isPresent(value) && !isPresent(key)) {
      return 'Key is required';
    }
  };
};

export default ({ fields, onAdd, nonEditableUptilIndex = -1 }) => {
  return (
    <ul class="list-unstyled notes">
      {fields.map((note, index) => {
        return (
          <li class="note" key={index}>
            <div class="key">
              {index > nonEditableUptilIndex && (
                <i
                  class="icon icon-close"
                  onClick={() => fields.remove(index)}
                />
              )}

              <Field
                name={`notes[${index}][key]`}
                component={InputField}
                class="form-control"
                placeholder="Title (key)"
                validate={required(index)}
                disabled={index <= nonEditableUptilIndex}
              />
            </div>

            <div class="value">
              <Field
                name={`notes[${index}][value]`}
                component={AutoResizeTextarea}
                rows="2"
                class="form-control"
                placeholder="Description (value)"
                disabled={index <= nonEditableUptilIndex}
              />
            </div>
          </li>
        );
      })}

      {fields.length < 10 ? (
        <li>
          <button
            class="btn btn-link add-note"
            type="button"
            onClick={() => fields.push({})}
          >
            Add Internal Note
          </button>
        </li>
      ) : null}
    </ul>
  );
};

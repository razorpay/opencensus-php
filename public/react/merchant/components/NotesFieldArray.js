import { Field } from 'redux-form';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';

export default ({ fields, onAdd }) => {
  return (
    <ul class="list-unstyled notes">
      {fields.map((note, index) => {
        return (
          <li class="note" key={index} style={{ marginBottom: '12px' }}>
            <div class="key">
              <i class="icon icon-close" onClick={() => fields.remove(index)} />
              <Field
                name={`notes[${index}][key]`}
                component="input"
                class="form-control"
                placeholder="Key"
              />
            </div>

            <div class="value">
              <Field
                name={`notes[${index}][value]`}
                component={AutoResizeTextarea}
                rows="2"
                class="form-control"
                placeholder="Value"
              />
            </div>
          </li>
        );
      })}

      {fields.length < 10
        ? <li>
            <button
              class="btn btn-link add-note"
              type="button"
              onClick={() => fields.push({})}
            >
              Add Internal Note
            </button>
          </li>
        : null}
    </ul>
  );
};

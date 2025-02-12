import Input from 'common/new-ui/Input';
import track from '../../track';

const Notes = (props) => (
  <Input.PairList
    className="Input--vTop"
    name="notes"
    label="Notes"
    labelClass="Input-label pb-8"
    onAddNew={track.lj.fields.notes}
    defaultValue={props.defaultValue}
    onChange={props.onChange}
    disabled={props.disabled}
  />
);

export default Notes;

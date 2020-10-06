import Input from 'common/new-ui/Input';
import track from '../../track';

const Notes = (props) => (
  <Input.PairList
    class="Input--vTop"
    name="notes"
    label="Notes"
    onAddNew={track.lj.fields.notes}
    defaultValue={props.defaultValue}
    onChange={props.onChange}
    disabled={props.disabled}
  />
);

export default Notes;

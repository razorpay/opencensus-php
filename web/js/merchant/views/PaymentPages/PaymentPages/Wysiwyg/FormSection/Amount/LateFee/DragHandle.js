import { sortableHandle } from 'react-sortable-hoc';

const DragHandle = sortableHandle(() => (
  <span className="dragHandle">
    <i className="i i-dotter" />
  </span>
));

export default DragHandle;

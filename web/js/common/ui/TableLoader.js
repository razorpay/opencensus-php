import Spinner from 'common/ui/Spinner';
import React from 'react';

export default (props) => {
  return (
    <tr>
      <td className="text-center empty-table" colSpan={props.colSpan}>
        <Spinner />
      </td>
    </tr>
  );
};

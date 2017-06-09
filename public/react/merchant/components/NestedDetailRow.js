// Blame @aseem for suggesting this name

import { Component } from 'react';
import DetailRow from 'merchant/components/DetailRow';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';
import TableBody from 'rzp/ui/TableBody';

export default ({ label, value = {} }) => {
  if (Object.keys(value).length) {
    return (
      <ListGroupToggler label={label} show={true}>
        <div class="table-responsive">
          <table class="table table-hover">
            <TableBody colSpan={2} rows={Object.keys(value)}>
              {Object.keys(value).map(key => (
                <DetailRow key={key} label={key} value={value[key]} />
              ))}
            </TableBody>
          </table>
        </div>
      </ListGroupToggler>
    );
  } else {
    return <DetailRow label={label} value="--" />;
  }
};

import React, { useRef, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import ModalHeader from '../../../../../common/ui/ModalHeader';
import DataTable from '../../../../../common/ui/Table/DataTable';
import { closeModal } from 'merchant_common/reducers/modals';
import { creditID, creditsDescription, credits, createdAt } from 'common/ui/item/pair';

function CreditTable(props) {
  const { closeModal, title, creditItems } = props;
  const node = useRef();

  useEffect(() => {
    document.addEventListener('mousedown', handleClick);
    return () => {
      document.removeEventListener('mousedown', handleClick);
    };
  }, []);

  const handleClick = (event) => {
    if (!node?.current?.contains(event?.target)) closeModal();
  };

  const columns = [creditID, creditsDescription, credits, createdAt];

  return (
    <div ref={node} className="credit-table-container">
      <div className="credit-table-container__body">
        <ModalHeader title={`${title}${' '}History`} onCloseClick={closeModal} />
        <div className="credit-table-container__content">
          <DataTable {...props} items={creditItems} columns={columns} />
        </div>
      </div>
      <div className="credit-table-container__footer">
        <button onClick={closeModal} className="btn btn-primary">
          Close
        </button>
      </div>
    </div>
  );
}
export default connect(null, { closeModal })(CreditTable);

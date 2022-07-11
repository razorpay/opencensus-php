import React, { useRef, useEffect } from 'react';
import { connect } from 'react-redux';
import ModalHeader from '../../../../../common/ui/ModalHeader';
import DataTable from '../../../../../common/ui/Table/DataTable';
import { closeModal } from 'merchant_common/reducers/modals';
import { creditID, creditsDescription, credits, createdAt } from 'common/ui/item/pair';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { getAnalyticsData } from '../ga';

function CreditTable(props) {
  const { closeModal, title, creditItems, type } = props;
  const node = useRef();

  const handleClick = (event) => {
    if (!node?.current?.contains(event?.target)) closeModal();
  };

  useEffect(() => {
    selfServeTrackSuccess(getAnalyticsData('view', type));
    document.addEventListener('mousedown', handleClick);
    return () => {
      document.removeEventListener('mousedown', handleClick);
    };
  }, []);

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

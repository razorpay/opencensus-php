import ShowWhen from 'merchant/components/ShowWhen';

const EmptyDailyList = (props) => {
  const { items, isAddMerchantView } = props;
  const { limit = 3 } = items;
  return (
    <div className="empty-daily-list">
      <div className="empty-table-message">
        <ShowWhen additionalCondition={() => isAddMerchantView}>
          <h3>Unlock your earnings view</h3>
          <p className="m-t">
            Add more accounts (&gt;{limit}) to unlock the details view of processed earnings
          </p>
          <p>
            <button className="btn btn-link" onClick={props.handleAddMerchant}>
              + Add New Account
            </button>
          </p>
        </ShowWhen>
        <ShowWhen additionalCondition={() => !isAddMerchantView}>
          <h4> No Data Found!</h4>
        </ShowWhen>
      </div>
    </div>
  );
};

export default EmptyDailyList;

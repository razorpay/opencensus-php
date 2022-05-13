import ShowWhen from 'merchant/components/ShowWhen';
import CommissionCardBody from 'merchant/views/PartnerDashboard/Commissions/components/FUX-Cards/CommissionCard/CardBody';

const EmptyDailyList = (props) => {
  const { isPartnershipFUX, items, isAddMerchantView } = props;
  const fuxEnabledClass = isPartnershipFUX ? ' fux-empty-daily-list' : '';
  const { limit = 3 } = items;
  return (
    <div className="empty-daily-list">
      <div className={`empty-table-message${fuxEnabledClass}`}>
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
      <ShowWhen additionalCondition={(currentUser) => currentUser.isPartnershipFUX}>
        <div className="daily-list-fux-cards">
          <p className="daily-list-msg">
            Earings will start showing after referrals, till then read more on{' '}
          </p>
          <div className="fux-commission-cards">
            <CommissionCardBody />
          </div>
        </div>
      </ShowWhen>
    </div>
  );
};

export default EmptyDailyList;

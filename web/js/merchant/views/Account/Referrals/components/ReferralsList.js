import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import CheckIcon from 'common/ui/CheckIcon';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const ReferralsListItem = props => {
  let user = props.user;
  let isAggregator = user.tags.indexOf('Aggregator') !== -1;
  let {
    id,
    name,
    email,
    activated,
    created_at,
    merchant_details = {},
  } = props.referral;

  return (
    <EntityItemRow id={id}>
      <td>
        {isAggregator ? (
          <a
            onClick={() => {
              props.switchMerchant(id);
            }}
          >
            {id}
          </a>
        ) : (
          <span>{id}</span>
        )}
      </td>
      <td>{name}</td>
      <td>{email}</td>
      <td>
        <Time value={created_at} format={'MMM Do, YYYY hh:mm:ss A'} />
      </td>
      <td>
        <CheckIcon value={merchant_details.submitted} />
      </td>
      <td>
        <CheckIcon value={activated} />
      </td>
      {email !== user.email &&
        props.showCreateLoginModal && (
          <td>
            <button
              className="btn btn-xs btn-primary"
              onClick={() => {
                props.showCreateLoginModal(props.referral);
              }}
              data-tip="Provide login to submerchant with merchant email."
              data-place="right"
            >
              Invite to Login
            </button>
          </td>
        )}
    </EntityItemRow>
  );
};

export default props => {
  let {
    referrals,
    isLoading,
    user,
    showCreateMerchantModal,
    showCreateLoginModal,
    switchMerchant,
  } = props;

  return (
    <div>
      <div className="table-responsive">
        <table className="table table-hover">
          <thead>
            <tr>
              <th>Merchant Id</th>
              <th>Name</th>
              <th>Email</th>
              <th>Registered At</th>
              <th>Submitted</th>
              <th>Activated</th>
              {showCreateLoginModal && <th>Actions</th>}
            </tr>
          </thead>
          <TableBody
            isLoading={isLoading}
            colSpan={7}
            rows={referrals}
            emptyTableRow={() => {
              if (isLoading || user.tags.indexOf('Aggregator') === -1) {
                return null;
              }
              return (
                <tr>
                  <td className="text-center empty-table" colSpan={7}>
                    {showCreateMerchantModal ? (
                      <button
                        className="btn btn-primary"
                        onClick={showCreateMerchantModal}
                      >
                        Create New Merchant
                      </button>
                    ) : (
                      'No Referrals Found'
                    )}
                  </td>
                </tr>
              );
            }}
          >
            {referrals.map(referral => (
              <ReferralsListItem
                key={referral.id}
                referral={referral}
                user={user}
                showCreateLoginModal={showCreateLoginModal}
                switchMerchant={switchMerchant}
              />
            ))}
          </TableBody>
        </table>
      </div>
    </div>
  );
};

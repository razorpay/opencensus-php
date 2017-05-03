import TableBody from '../TableBody';
import Time from 'rzp/ui/Time';
import CheckIcon from 'rzp/ui/CheckIcon';
import Referral from 'merchant/models/Referral';

const ReferralsListItem = props => {
  let mode = props.mode;
  let user = props.user;
  let canHighlight = props.canHighlight;
  let {
    id,
    name,
    email,
    activated,
    created_at,
    merchant_details = {},
  } = props.referral;

  return (
    <tr class={canHighlight ? 'luminate' : ''}>
      <td>
        {id}
      </td>
      <td>
        {name}
      </td>
      <td>
        {email}
      </td>
      <td>
        <Time value={created_at} format={'MMM Do, YYYY hh:mm:ss A'} />
      </td>
      <td>
        <CheckIcon value={merchant_details.submitted === 1} />
      </td>
      <td>
        <CheckIcon value={activated === 1} />
      </td>
      <td>
        {email !== user.email &&
          <button
            class="btn btn-xs btn-primary"
            onClick={() => {
              props.showCreateLoginModal(props.referral);
            }}
          >
            Create Login
          </button>}
      </td>
    </tr>
  );
};

export default props => {
  let {
    referrals,
    isLoading,
    user,
    showCreateLoginModal,
    highlightRow,
  } = props;

  return (
    <div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Merchant Id</th>
              <th>Name</th>
              <th>Email</th>
              <th>Registered At</th>
              <th>Submitted</th>
              <th>Activated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <TableBody isLoading={isLoading} colSpan={7} rows={referrals}>
            {referrals.map(referral => (
              <ReferralsListItem
                key={referral.id}
                referral={referral}
                user={user}
                canHighlight={highlightRow(referral)}
                showCreateLoginModal={showCreateLoginModal}
              />
            ))}
          </TableBody>
        </table>
      </div>
    </div>
  );
};

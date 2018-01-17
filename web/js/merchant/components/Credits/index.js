import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import Alert from 'rzp/ui/Forms/Alert';
import HeaderAction from 'rzp/ui/HeaderAction';

export default props => {
  let { creditsData, balanceData, loading, error, currentUser } = props;

  if (!currentUser) {
    error =
      'Your user account is not associated at present with any active merchant account.';
  }

  return (
    <div class="content-wrapper content-sm">
      <HeaderAction>
        <div class="btn-toolbar pull-right">
          <a
            class="btn btn-link"
            href="https://docs.razorpay.com/v1/page/credits"
            target="_blank"
          >
            Documentation &nbsp;
            <i class="icon icon-external-link" />
          </a>
        </div>
      </HeaderAction>

      {loading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div>
          <Alert type="error" message={error} />

          <div class="list-group details-row-container">
            {balanceData.credits ? (
              <div class="list-group-item">
                <span>Amount Credits</span>
                <Amount value={balanceData.credits} />
              </div>
            ) : null}

            {balanceData.fee_credits ? (
              <div class="list-group-item">
                <span>Fee Credits</span>
                <Amount value={balanceData.fee_credits} />
              </div>
            ) : null}

            <div
              class={`list-group-item ${
                creditsData.items.length ? 'no-flex' : ''
              }`}
            >
              <span>Credits</span>
              {!creditsData.items.length ? (
                <span>No credits Assigned</span>
              ) : (
                <div class="panel-body">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Id</th>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Created At</th>
                      </tr>
                    </thead>
                    <tbody>
                      {creditsData.items.map((credit, index) => {
                        return (
                          <tr key={credit.id}>
                            <td>{credit.id}</td>
                            <td>{credit.campaign}</td>
                            <td>{credit.type}</td>
                            <td>
                              <Amount value={credit.value} />
                            </td>
                            <td>
                              <Time
                                value={credit.created_at}
                                format="DD/MM/YYYY H:mm a"
                              />
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

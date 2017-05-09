import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({ user }) => {
  let isMerchant = !!user.current;

  return (
    <div class="sidebar">
      <section class="brand-logo">
        <a href="#/">
          <img src="img/logo_full.png" />
        </a>
      </section>
      <nav>
        {!isMerchant
          ? null
          : <ul class="nav">
              <ShowWhen notMyRole="sellerapp">
                <li>
                  <NavLink to="/payments">Transactions</NavLink>
                </li>
              </ShowWhen>

              <ShowWhen notMyRole="sellerapp">
                <li>
                  <NavLink to="/settlements">Settlements</NavLink>
                </li>
              </ShowWhen>

              <li>
                <NavLink to="/invoices">Invoices</NavLink>
              </li>

              <ShowWhen myRole="owner">
                <li>
                  <NavLink to="/team">Manage Team</NavLink>
                </li>
              </ShowWhen>
            </ul>}
      </nav>
    </div>
  );
};

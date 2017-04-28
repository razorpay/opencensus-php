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
                  <NavLink to="/orders">Orders</NavLink>
                </li>
              </ShowWhen>

              <ShowWhen notMyRole="sellerapp">
                <li>
                  <NavLink to="/refunds">Refunds</NavLink>
                </li>
              </ShowWhen>
            </ul>}
      </nav>
    </div>
  );
};

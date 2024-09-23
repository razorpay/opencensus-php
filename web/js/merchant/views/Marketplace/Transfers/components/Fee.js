import React from 'react';

import Amount from 'common/ui/Amount';
import Blockquote from 'common/ui/Blockquote';
import { useStore } from 'shell/commonStore';

/*
 * Design
 * https://projects.invisionapp.com/d/main#/console/11823589/249792186/preview
 *
 * Input
 * @param {Number} totalFee
 * @param {Number} rzpFee
 * @param {Number} tax
 *
 * Description
 * Given totalFee , Raxorpay Fee and Tax as props component will be rendered as per
 * the design
 *
 * Usage:
 * <Fee totalFee={} rzpFee={} tax={}/>
 */

const FeeTable = ({ rows }) => (
  <table>
    <tbody>
      {rows.map((item, index) => (
        <tr key={index}>
          <td>{item[0]}</td>
          <td>{item[1]}</td>
        </tr>
      ))}
    </tbody>
  </table>
);

export default ({ totalFee = 0, rzpFee = 0, tax = 0, currency = 'INR' }) => {
  const user = useStore((state) => state.session.user);

  return (
    <div class="rzp-fee">
      <div class="m-b text-small">
        <FeeTable rows={[['Total Fee', <Amount value={totalFee} currency={currency} />]]} />
      </div>
      <div class="text-fade">
        <Blockquote>
          <FeeTable
            rows={[
              ['Razorpay Fee', <Amount value={rzpFee} currency={currency} />],
              [
                user.isCountryIndia ? 'GST(18%)' : 'Tax',
                <Amount value={tax} currency={currency} />,
              ],
            ]}
          />
        </Blockquote>
      </div>
    </div>
  );
};
